<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Support\Arr;

class DropIndexBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->statement = new DropIndexStatement($table);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->statement->name = $name;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function column(string $column)
    {
        $this->statement->columns[] = $column;
        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function columns($columns)
    {
        foreach (Arr::wrap($columns) as $column) {
            $this->column($column);
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->statementForDropIndex($this->statement)
        ];
    }
}
