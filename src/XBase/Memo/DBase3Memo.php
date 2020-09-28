<?php declare(strict_types=1);

namespace XBase\Memo;

class DBase3Memo extends AbstractWritableMemo
{
    const BLOCK_LENGTH = 512;

    public function get(int $pointer): ?MemoObject
    {
        if (!$this->isOpen()) {
            $this->open();
        }

        $this->fp->seek($pointer * self::BLOCK_LENGTH);

        $endMarker = $this->getBlockEndMarker();
        $result = '';
        $memoLength = 0;
        while (!$this->fp->eof()) { //todo too slow need speedup
            $memoLength++;
            $result .= $this->fp->read(1);

            $substr = substr($result, -3);
            if ($endMarker === $substr) {
                $result = substr($result, 0, -3);
                break;
            }
        }

        $type = $this->guessDataType($result);
        if (MemoObject::TYPE_TEXT === $type) {
            if (chr(0x00) === substr($result, -1)) {
                $result = substr($result, 0, -1); // remove endline symbol (0x00)
            }
            if ($this->convertFrom) {
                $result = iconv($this->convertFrom, 'utf-8', $result);
            }
        }

        return new MemoObject($result, $type, $pointer, $memoLength);
    }

    protected function calculateBlockCount(string $data): int
    {
        return (int) ceil(strlen($data) + strlen($this->getBlockEndMarker()) / self::BLOCK_LENGTH);
    }

    private function getBlockEndMarker(): string
    {
        return chr(0x1A).chr(0x1A).chr(0x00);
    }

    protected function getBlockSize(): int
    {
        return self::BLOCK_LENGTH;
    }
}
