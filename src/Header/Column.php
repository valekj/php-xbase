<?php declare(strict_types=1);

namespace XBase\Header;

/**
 * @property int $columnIndex
 *
 * DBase
 * @property string $reserved1
 * @property string $reserved2
 * @property string $reserved3
 *
 * DBase7
 * @property int $mdxFlag
 * @property int $nextAI
 */
class Column
{
    /** @var string */
    public $name;

    /** @var string|null */
    public $rawName;

    /** @var string */
    public $type;

    /**
     * @var int Data starts from index
     */
    public $bytePosition;

    /** @var int */
    public $length;

    /** @var int|null */
    public $decimalCount = 0;

    /** @var int Field address within record. */
    public $memAddress;

    /** @var int|null */
    public $workAreaID;

    /** @var bool|null */
    public $setFields = false;

    /** @var bool|null */
    public $indexed = false;

    /** @var array */
    private $properties = [];

    public function __set(string $property, $value): void
    {
        $this->properties[$property] = $value;
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
    {
        return $this->properties[$property];
    }

    public function __construct(array $properties = [])
    {
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }
    }
}
