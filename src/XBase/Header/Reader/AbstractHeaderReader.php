<?php declare(strict_types=1);

namespace XBase\Header\Reader;

use XBase\Exception\TableException;
use XBase\Header\HeaderInterface;
use XBase\Header\Reader\Column\ColumnReaderFactory;
use XBase\Stream\Stream;

abstract class AbstractHeaderReader implements HeaderReaderInterface
{
    /** @var static */
    protected $filepath;

    /** @var Stream */
    protected $fp;

    /** @var HeaderInterface|null */
    protected $header;

    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        $this->fp = Stream::createFromFile($filepath);
    }

    public static function getHeaderLength(): int
    {
        return 32;
    }

    public static function getFieldLength(): int
    {
        return 32;
    }

    public function read(): HeaderInterface
    {
        $this->fp->seek(0);

        $this->readFirstBlock();
        $this->readColumns();
        $this->readRest();

        $this->fp->close();

        return $this->header;
    }

    protected function readFirstBlock(): void
    {
        $refClass = new \ReflectionClass($this->getClass());
        $namedArguments = $this->extractArgs();
        //the values in the array are mapped to constructor arguments positionally
        $this->header = $refClass->newInstanceArgs(array_values($namedArguments));
    }

    protected function readColumns(): void
    {
        [$columnsCount, $terminatorLength] = $this->pickColumnsCount();

        /* some checking */
        clearstatcache();
        if ($this->header->getLength() > filesize($this->filepath)) {
            throw new TableException(sprintf('File %s is not DBF', $this->filepath));
        }

        if ($this->header->getLength() + ($this->header->getRecordCount() * $this->header->getRecordByteLength()) - 500 > filesize($this->filepath)) {
            throw new TableException(sprintf('File %s is not DBF', $this->filepath));
        }

        $bytePos = 1;
        $columnReader = ColumnReaderFactory::create($this->header->getVersion());
        $index = 0;
        for ($i = 0; $i < $columnsCount; $i++) {
            $memoryChunk = $this->fp->read($columnReader::getHeaderLength());
            $column = $columnReader->read($memoryChunk, $index++, $bytePos);
            $bytePos += $column->getLength();
            $this->header->addColumn($column);
        }

        $this->checkHeaderTerminator($terminatorLength);
    }

    protected function readRest(): void
    {
    }

    /**
     * @return array named argument for certain implementation of HeaderInterface
     */
    protected function extractArgs(): array
    {
        $args = [
            'version'          => $this->fp->readUChar(),
            'modifyDate'       => $this->fp->read3ByteDate(),
            'recordCount'      => $this->fp->readUInt(),
            'headerLength'     => $this->fp->readUShort(),
            'recordByteLength' => $this->fp->readUShort(),
        ];
        $this->fp->read(2); //reserved
        $args['inTransaction'] = 0 !== $this->fp->readUChar();
        $args['encrypted'] = 0 !== $this->fp->readUChar();
        $this->fp->read(4); //Free record thread
        $this->fp->read(8); //Reserved for multi-user dBASE
        $args['mdxFlag'] = $this->fp->readUChar();
        $args['languageCode'] = $this->fp->readUChar();
        $this->fp->read(2); //reserved

        return $args;
    }

    /**
     * @return array [$fieldCount, $terminatorLength]
     */
    protected function pickColumnsCount(): array
    {
        // some files has headers with 2byte-terminator 0xOD00
        foreach ([1, 2] as $terminatorLength) {
            $fieldCount = $this->getLogicalFieldCount($terminatorLength);
            if (is_int($fieldCount)) {
                return [$fieldCount, $terminatorLength];
            }
        }

        throw new \LogicException('Wrong fieldCount calculation');
    }

    /**
     * @return float|int
     */
    protected function getLogicalFieldCount(int $terminatorLength = 1)
    {
        $headerLength = static::getHeaderLength() + $terminatorLength; // [Terminator](1)
        $extraSize = $this->header->getLength() - $headerLength;

        return $extraSize / static::getFieldLength();
    }

    /**
     * @throws TableException
     */
    private function checkHeaderTerminator(int $terminatorLength): void
    {
        $terminator = $this->fp->read($terminatorLength);
        switch ($terminatorLength) {
            case 1:
                if (chr(0x0D) !== $terminator) {
                    throw new TableException('Expected header terminator not present at position '.$this->fp->tell());
                }
                break;

            case 2:
                $unpack = unpack('n', $terminator);
                if (0x0D00 !== $unpack[1]) {
                    throw new TableException('Expected header terminator not present at position '.$this->fp->tell());
                }
                break;
        }
    }
}
