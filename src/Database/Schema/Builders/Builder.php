<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\BaseStatement;
use Kirameki\Database\Schema\Formatters\Formatter;
use Kirameki\Support\Concerns\Tappable;

abstract class Builder
{
    use Tappable;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var BaseStatement
     */
    protected $statement;

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        $this->statement->table = $table;
        return $this;
    }

    /**
     * @return static
     */
    protected function copy()
    {
        return clone $this;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    abstract public function toSql(): string;
}
