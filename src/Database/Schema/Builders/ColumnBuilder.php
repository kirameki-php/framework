<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Support\CurrentTimestamp;
use Kirameki\Database\Support\Expr;

class ColumnBuilder
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
     * @return $this
     */
    public function primaryKey()
    {
        $this->definition->primaryKey = true;
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
     * @return $this
     */
    public function autoIncrement()
    {
        $this->definition->autoIncrement = true;
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

    /**
     * @return $this
     */
    public function currentAsDefault()
    {
        return $this->default(CurrentTimestamp::instance());
    }
}
