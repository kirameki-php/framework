<?php

namespace Kirameki\Database\Schema;

class Column
{
    protected string $name;

    protected string $type;

    protected ?bool $primaryKey;

    protected ?bool $nullable;

    protected ?bool $autoIncrement;

    protected ?string $comment;

    protected $default;

    /**
     * @param string $name
     * @param string $type
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
        $this->primaryKey = null;
        $this->nullable = null;
        $this->autoIncrement = null;
        $this->comment = null;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function primaryKey(bool $toggle = true)
    {
        $this->primaryKey = $toggle;
        return $this;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function nullable(bool $toggle = true)
    {
        $this->nullable = $toggle;
        return $this;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function autoIncrement(bool $toggle = true)
    {
        $this->autoIncrement = $toggle;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function default($value)
    {
        $this->default = $value;
        return $this;
    }
}
