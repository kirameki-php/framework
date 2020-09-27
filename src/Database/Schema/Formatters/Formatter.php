<?php

namespace Kirameki\Database\Schema\Formatters;

use DateTimeInterface;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Support\Expr;
use Kirameki\Database\Query\Statements\BaseStatement;
use Kirameki\Database\Query\Statements\ConditionalStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Schema\Statements\ColumnConstraint;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Support\Arr;
use RuntimeException;

class Formatter
{
    protected Connection $connection;

    protected string $quote = '`';

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    public function statementForCreate(CreateTableStatement $statement): string
    {
        $parts = [];
        $parts[]= 'CREATE TABLE';
        $parts[]= $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $column) {
            $columnParts[]= $column->toSql($this);
        }
        $parts[] = '('.implode(', ', $columnParts).')';
        return implode(' ', $parts).';';
    }

    /**
     * @param string $name
     * @param string $type
     * @param ColumnConstraint $constraint
     * @return string
     */
    public function column(string $name, string $type, ColumnConstraint $constraint): string
    {
        $parts = [];
        $parts[] = $name;
        $parts[] = $this->columnType($type, $constraint->size, $constraint->scale);
        if (!$constraint->nullable) {
            $parts[] = 'NOT NULL';
        }
        if ($constraint->default !== null) {
            $parts[] = 'DEFAULT '.$this->value($type, $constraint->default);
        }
        if ($constraint->autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }
        if ($constraint->comment !== null) {
            $parts[] = 'COMMENT '.$this->stringLiteral($constraint->comment);
        }
        return implode(' ', $parts);
    }

    protected function columnType(string $type, ?int $size, ?int $scale)
    {
        if ($type === 'int') {
            if ($size === null) return 'BIGINT';
            if ($size === 1) return 'TINYINT';
            if ($size === 2) return 'SMALLINT';
            if ($size === 4) return 'INT';
            if ($size === 8) return 'BIGINT';
        }
        if ($type === 'decimal') {
            $args = Arr::compact([$size, $scale]);
            return 'DECIMAL'.(!empty($args) ? '('.implode(',', $args).')' : '');
        }
        if ($type === 'datetime') {
            return 'DATETIME('.($size ?? 6).')';
        }
        if ($type === 'string') {
            return 'VARCHAR('.($size ?? 191).')';
        }
        if ($type === 'uuid') {
            return 'VARCHAR(36)';
        }
        $args = Arr::compact([$size, $scale]);
        return strtoupper($type).(!empty($args) ? '('.implode(',', $args).')' : '');
    }

    /**
     * @param string $text
     * @return string
     */
    protected function addQuotes(string $text)
    {
        $quoted = $this->quote;
        $quoted.= str_replace($this->quote, $this->quote.$this->quote, $text);
        $quoted.= $this->quote;
        return $quoted;
    }

    protected function value(string $type, $value)
    {
        if (is_string($value)) {
            return $this->stringLiteral($value);
        }
        return $value;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function stringLiteral(string $str)
    {
        return "'".str_replace("'", "''", $str)."'";
    }
}
