<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Schema\Statements\ColumnDefinition;

class Column
{
    /**
     * @var ColumnDefinition
     */
    protected ColumnDefinition $definition;

    /**
     * @param ColumnDefinition $definition
     */
    public function __construct(ColumnDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function primaryKey(bool $toggle = true)
    {
        $this->definition->primaryKey = $toggle;
        return $this;
    }

    /**
     * @return $this
     */
    public function notNull()
    {
        $this->definition->nullable = false;
        return $this;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function autoIncrement(bool $toggle = true)
    {
        $this->definition->autoIncrement = $toggle;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment)
    {
        $this->definition->comment = $comment;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function default($value)
    {
        $this->definition->default = $value;
        return $this;
    }
}
