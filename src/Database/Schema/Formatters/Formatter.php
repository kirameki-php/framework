<?php

namespace Kirameki\Database\Schema\Formatters;

use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\Statement;
use Kirameki\Database\Support\Expr;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Support\Arr;

class Formatter
{
    /**
     * @var string
     */
    protected string $quote = '`';

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    public function statementForCreateTable(CreateTableStatement $statement): string
    {
        $parts = [];
        $parts[]= 'CREATE TABLE';
        $parts[]= $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $definition) {
            $columnParts[]= $this->column($definition);
        }
        $parts[] = '('.implode(', ', $columnParts).')';
        return implode(' ', $parts).';';
    }

    /**
     * @param Statement $statement
     * @return string
     */
    public function statementForDropTable(Statement $statement): string
    {
        return 'DROP TABLE '.$statement->table.';';
    }

    /**
     * @param CreateIndexStatement $statement
     * @return string
     */
    public function statementForCreateIndex(CreateIndexStatement $statement): string
    {
        $columnParts = [];
        foreach ($statement->columns as $column => $order) {
            $columnParts[] = is_string($column) ? "$column $order" : $order;
        }

        $parts = [];
        $parts[]= 'CREATE';
        if ($statement->unique) {
            $parts[] = 'UNIQUE';
        }
        $parts[]= 'INDEX';
        $parts[]= $statement->name ?? implode('_', array_merge([$statement->table], $columnParts));
        $parts[]= 'ON';
        $parts[]= $statement->table;
        $parts[] = '('.implode(', ', $columnParts).')';
        if ($statement->comment !== null) {
            $parts[]= $this->stringLiteral($statement->comment);
        }
        return implode(' ', $parts).';';
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    public function column(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = $def->name;
        $parts[] = $this->columnType($def->type, $def->size, $def->scale);
        if (!$def->nullable) {
            $parts[] = 'NOT NULL';
        }
        if ($def->default !== null) {
            $parts[] = 'DEFAULT '.$this->value($def->type, $def->default);
        }
        if ($def->autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }
        if ($def->comment !== null) {
            $parts[] = 'COMMENT '.$this->stringLiteral($def->comment);
        }
        return implode(' ', $parts);
    }

    /**
     * @param string $type
     * @param int|null $size
     * @param int|null $scale
     * @return string
     */
    protected function columnType(string $type, ?int $size, ?int $scale): string
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
    protected function addQuotes(string $text): string
    {
        $quoted = $this->quote;
        $quoted.= str_replace($this->quote, $this->quote.$this->quote, $text);
        $quoted.= $this->quote;
        return $quoted;
    }

    /**
     * @param string $type
     * @param $value
     * @return string
     */
    protected function value(string $type, $value): string
    {
        if ($value instanceof Expr) {
            return $value->toString();
        }
        if (is_string($value)) {
            return $this->stringLiteral($value);
        }
        return $value;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function stringLiteral(string $str): string
    {
        return "'".str_replace("'", "''", $str)."'";
    }
}
