<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Column;
use Kirameki\Database\Schema\ColumnAggregate;
use Kirameki\Database\Schema\Statements\CreateTableStatement;

class CreateTableBuilder extends Builder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->statement = new CreateTableStatement($table);
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return Column
     */
    public function int(string $column, ?int $size = null)
    {
        return $this->column($column, __FUNCTION__)->size($size);
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return Column
     */
    public function float(string $column, ?int $size = null)
    {
        return $this->column($column, __FUNCTION__)->size($size);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @param int|null $scale
     * @return Column
     */
    public function decimal(string $column, ?int $precision = null, ?int $scale = null)
    {
        return $this->scalableColumn($column, __FUNCTION__, $precision, $scale);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function bool(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function date(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @return Column
     */
    public function datetime(string $column, ?int $precision = null)
    {
        return $this->column($column, __FUNCTION__)->size($precision);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function time(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return Column
     */
    public function string(string $column, ?int $size = null)
    {
        return $this->column($column, __FUNCTION__)->size($size);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function text(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function json(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function binary(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function uuid(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    public function timestamps()
    {
        return new ColumnAggregate([
            $this->datetime('createdAt'),
            $this->datetime('updatedAt'),
        ]);
    }

    /**
     * @param string $name
     * @param string $type
     * @return Column
     */
    protected function column(string $name, string $type)
    {
        $this->statement->columns ??= [];
        return $this->statement->columns[] = new Column($name, $type);
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $precision
     * @param int|null $scale
     * @return Column
     */
    public function scalableColumn(string $name, string $type, ?int $precision, ?int $scale)
    {
        return $this->column($name, $type)->size($precision)->scale($scale);
    }
}