<?php declare(strict_types=1);

namespace XBase\Header;

/**
 * @internal
 *
 * @author Alexander Strizhak <gam6itko@gmail.com>
 *
 * DBase7
 *
 * @property string|null $languageName
 *
 * VisualFoxpro
 * @property string|null $backlist
 */
class Header
{
    /**
     * @var int
     */
    public $version;

    /**
     * @var int Unix time
     */
    public $modifyDate;

    /**
     * @var int
     */
    public $recordCount = 0;

    /**
     * @var int
     */
    public $recordByteLength = 0;

    /**
     * @var bool
     */
    public $inTransaction = false;

    /**
     * @var bool
     */
    public $encrypted = false;

    /** @var int */
    public $mdxFlag = 0;

    /**
     * @var int language codepage
     *
     * @see https://blog.codetitans.pl/post/dbf-and-language-code-page/
     */
    public $languageCode = 0;

    /**
     * @var Column[]
     */
    public $columns = [];

    /**
     * @var int
     */
    public $length;

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
