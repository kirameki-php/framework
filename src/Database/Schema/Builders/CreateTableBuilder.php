<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Support\Expr;

class CreateTableBuilder extends StatementBuilder
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
     * @return ColumnBuilder
     */
    public function int(string $column, ?int $size = null)
    {
        return $this->sizeableColumn($column, __FUNCTION__, $size);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function float(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function double(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @param int|null $scale
     * @return ColumnBuilder
     */
    public function decimal(string $column, ?int $precision = null, ?int $scale = null)
    {
        return $this->scalableColumn($column, __FUNCTION__, $precision, $scale);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function bool(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function date(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $precision
     * @return ColumnBuilder
     */
    public function datetime(string $column, ?int $precision = null)
    {
        return $this->sizeableColumn($column, __FUNCTION__, $precision);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function time(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @param int|null $size
     * @return ColumnBuilder
     */
    public function string(string $column, ?int $size = null)
    {
        return $this->sizeableColumn($column, __FUNCTION__, $size);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function text(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function json(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function binary(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @param string $column
     * @return ColumnBuilder
     */
    public function uuid(string $column)
    {
        return $this->column($column, __FUNCTION__);
    }

    /**
     * @return ColumnBuilderAggregate
     */
    public function timestamps()
    {
        return new ColumnBuilderAggregate([
            $this->datetime('createdAt')->default(Expr::raw('CURRENT_TIMESTAMP')),
            $this->datetime('updatedAt')->default(Expr::raw('CURRENT_TIMESTAMP')),
        ]);
    }

    /**
     * @param string $name
     * @param string $type
     * @return ColumnBuilder
     */
    public function column(string $name, string $type)
    {
        $definition = new ColumnDefinition($name, $type);
        $this->statement->columns ??= [];
        $this->statement->columns[] = $definition;
        return new ColumnBuilder($definition);
    }

    /**
     * @param string ...$columns
     * @return CreateIndexBuilder
     */
    public function index(string ...$columns)
    {
        $this->statement->indexes ??= [];
        $statement = new CreateIndexStatement($this->statement->table, $columns);
        $builders = new CreateIndexBuilder($this->connection, $statement);
        $this->statement->indexes[] = $statement;
        return $builders;
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $size
     * @return ColumnBuilder
     */
    protected function sizeableColumn(string $name, string $type, ?int $size)
    {
        $definition = new ColumnDefinition($name, $type);
        $definition->size = $size;
        $this->statement->columns ??= [];
        $this->statement->columns[] = $definition;
        return new ColumnBuilder($definition);
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $precision
     * @param int|null $scale
     * @return ColumnBuilder
     */
    public function scalableColumn(string $name, string $type, ?int $precision, ?int $scale)
    {
        $definition = new ColumnDefinition($name, $type);
        $definition->size = $precision;
        $definition->scale = $scale;
        $this->statement->columns ??= [];
        $this->statement->columns[] = $definition;
        return new ColumnBuilder($definition);
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        $ddls = [];
        $ddls[] = $formatter->statementForCreateTable($this->statement);
        foreach ($this->statement->indexes as $indexStatement) {
            $ddls[] = $formatter->statementForCreateIndex($indexStatement);
        }
        return $ddls;
    }
}