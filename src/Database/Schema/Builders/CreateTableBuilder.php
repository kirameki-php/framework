<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Support\Expr;

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
        return $this->sizeableColumn($column, __FUNCTION__, $size);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function float(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function double(string $column)
    {
        return $this->column($column, __FUNCTION__);
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
        return $this->sizeableColumn($column, __FUNCTION__, $precision);
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
        return $this->sizeableColumn($column, __FUNCTION__, $size);
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

    /**
     * @return ColumnAggregate
     */
    public function timestamps()
    {
        return new ColumnAggregate([
            $this->datetime('createdAt')->default(Expr::raw('CURRENT_TIMESTAMP')),
            $this->datetime('updatedAt')->default(Expr::raw('CURRENT_TIMESTAMP')),
        ]);
    }

    /**
     * @param string $name
     * @param string $type
     * @return Column
     */
    public function column(string $name, string $type)
    {
        $this->statement->columns ??= [];
        return $this->statement->columns[] = new Column($name, $type);
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $size
     * @return Column
     */
    protected function sizeableColumn(string $name, string $type, ?int $size)
    {
        $this->statement->columns ??= [];
        return $this->statement->columns[] = Column::sizeable($name, $type, $size);
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
        $this->statement->columns ??= [];
        return $this->statement->columns[] = Column::scalable($name, $type, $precision, $scale);
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $formatter = $this->connection->getSchemaFormatter();
        return $formatter->statementForCreate($this->statement);
    }
}