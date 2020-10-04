<?php

namespace Kirameki\Database\Schema\Formatters;

use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\AlterTableStatement;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\BaseStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
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
    public function createTableStatement(CreateTableStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE TABLE';
        $parts[] = $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $definition) {
            $columnParts[] = $this->column($definition);
        }
        $pkParts = [];
        foreach ($statement->primaryKey->columns as $column => $order) {
            $pkParts[] = "$column $order";
        }
        if (!empty($pkParts)) {
            $columnParts[] = 'PRIMARY KEY ('.implode(', ', $pkParts).')';
        }
        $parts[] = '('.implode(', ', $columnParts).')';
        return implode(' ', $parts).';';
    }

    /**
     * @param AlterColumnAction $action
     * @return string
     */
    public function addColumnAction(AlterColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'ADD COLUMN';
        $parts[] = $this->column($action->definition);
        $parts[] = $action->positionType;
        $parts[] = $action->positionColumn;
        return implode(' ', array_filter($parts));
    }

    /**
     * @param AlterColumnAction $action
     * @return string
     */
    public function modifyColumnAction(AlterColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'MODIFY COLUMN';
        $parts[] = $this->column($action->definition);
        $parts[] = $action->positionType;
        $parts[] = $action->positionColumn;
        return implode(' ', array_filter($parts));
    }

    /**
     * @param AlterDropColumnAction $action
     * @return string
     */
    public function dropColumnAction(AlterDropColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'DROP COLUMN';
        $parts[] = $this->addQuotes($action->column);
        return implode(' ', $parts);
    }

    /**
     * @param AlterRenameColumnAction $action
     * @return string
     */
    public function renameColumnAction(AlterRenameColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'RENAME COLUMN';
        $parts[] = $this->addQuotes($action->from);
        $parts[] = 'TO';
        $parts[] = $this->addQuotes($action->to);
        return implode(' ', $parts);
    }

    /**
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameTableStatement(string $from, string $to): string
    {
        return 'ALTER TABLE '.$this->addQuotes($from).' RENAME TO '.$this->addQuotes($to).';';
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    public function dropTableStatement(BaseStatement $statement): string
    {
        return 'DROP TABLE '.$statement->table.';';
    }

    /**
     * @param CreateIndexStatement $statement
     * @return string
     */
    public function createIndexStatement(CreateIndexStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE';
        if ($statement->unique) {
            $parts[] = 'UNIQUE';
        }
        $parts[] = 'INDEX';
        $parts[] = $statement->name ?? implode('_', array_merge([$statement->table], array_keys($statement->columns)));
        $parts[] = 'ON';
        $parts[] = $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $column => $order) {
            $columnParts[] = "$column $order";
        }
        $parts[] = '('.implode(', ', $columnParts).')';
        if ($statement->comment !== null) {
            $parts[] = $this->stringLiteral($statement->comment);
        }
        return implode(' ', $parts).';';
    }

    /**
     * @param DropIndexStatement $statement
     * @return string
     */
    public function dropIndexStatement(DropIndexStatement $statement): string
    {
        $name = $statement->name ?? implode('_', array_merge([$statement->table], $statement->columns));
        return 'DROP INDEX '.$name.' ON '.$statement->table.';';
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
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
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
