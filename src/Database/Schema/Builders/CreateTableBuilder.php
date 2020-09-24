<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Column;
use Kirameki\Database\Schema\Statements\CreateTableStatement;

class CreateTableBuilder extends Builder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->statement = new CreateTableStatement($table);
    }

    public function int64(string $column)
    {

    }

    public function string(string $column, int $size)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function uuid(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $name
     * @param string $type
     * @return $this
     */
    protected function column(string $name, string $type)
    {
        $this->statement->columns = new Column($name, $type);
        return $this;
    }

    public function inspect(): array
    {
    }
}