<?php

namespace Kirameki\Database\Query;

use DateTimeInterface;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\BaseStatement;
use Kirameki\Database\Query\Statements\ConditionStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;

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
     * @param SelectStatement $statement
     * @return string
     */
    public function statementForSelect(SelectStatement $statement): string
    {
        return implode(' ', [
            $this->select($statement),
            $this->from($statement),
            $this->where($statement),
            $this->order($statement),
            $this->limit($statement),
            $this->offset($statement),
        ]);
    }

    /**
     * @param SelectStatement $statement
     * @return array
     */
    public function bindingsForSelect(SelectStatement $statement): array
    {
        return $this->bindingsForCondition($statement);
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    public function statementForInsert(InsertStatement $statement): string
    {
        $columnsAssoc = [];
        $listSize = 0;
        foreach ($statement->dataset as $data) {
            foreach($data as $name => $value) {
                if ($value !== null) {
                    $columnsAssoc[$name] = null;
                }
            }
            $listSize++;
        }

        $columnNames = array_keys($columnsAssoc);
        $cloumnCount = count($columnNames);

        $placeholders = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $cloumnCount; $j++) {
                $binders[] = $this->bindName();
            }
            $placeholders[] = '('.implode(', ', $binders).')';
        }

        return implode(' ', [
            'INSERT INTO',
            $this->tableName($statement->table),
            '('.implode(', ', $columnNames).')',
            'VALUES',
            implode(', ', $placeholders),
        ]);
    }

    /**
     * @param InsertStatement $statement
     * @return array
     */
    public function bindingsForInsert(InsertStatement $statement): array
    {
        $bindings = [];
        foreach ($statement->dataset as $data) {
            foreach ($data as $value) {
                $bindings[] = $value;
            }
        }
        return $bindings;
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    public function statementForUpdate(UpdateStatement $statement): string
    {
        $assignments = [];
        foreach (array_keys($statement->data) as $name) {
            $assignments[] = $name.' = ?';
        }

        return implode(' ', [
            'UPDATE',
            $this->tableName($statement->table),
            'SET',
            implode(', ', $assignments),
            $this->where($statement),
            $this->order($statement),
            $this->limit($statement),
        ]);
    }

    /**
     * @param UpdateStatement $statement
     * @return array
     */
    public function bindingsForUpdate(UpdateStatement $statement): array
    {
        return array_merge($statement->data, $this->bindingsForCondition($statement));
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function statementForDelete(DeleteStatement $statement): string
    {
        return implode(' ', [
            'DELETE FROM',
            $this->tableName($statement->table),
            $this->where($statement),
            $this->order($statement),
            $this->limit($statement),
        ]);
    }

    /**
     * @param DeleteStatement $statement
     * @return array
     */
    public function bindingsForDelete(DeleteStatement $statement): array
    {
        return $this->bindingsForCondition($statement);
    }

    /**
     * FOR DEBUGGING ONLY
     *
     * @param string $statement
     * @param array $bindings
     * @return string
     */
    public function interpolate(string $statement, array $bindings): string
    {
        return preg_replace_callback('/\?\??/', static function($matches) use (&$bindings) {
            if ($matches[0] === '?') {
                $current = current($bindings);
                next($bindings);
                if (is_null($current)) return 'NULL';
                if (is_bool($current)) return $current ? 'TRUE' : 'FALSE';
                if (is_string($current)) return $this->connection->getPdo()->quote($current);
                return $this->parameter($current);
            }
            return $matches[0];
        }, $statement);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function select(SelectStatement $statement): string
    {
        if (empty($statement->columns)) {
            $statement->columns[] = '*';
        }
        $exprs = [];
        $table = $statement->tableAlias ?? $statement->table;
        foreach ($statement->columns as $name) {
            $segments = preg_split('/\s+as\s+/i', $name);
            if (count($segments) > 1) {
                $exprs[] = $this->columnName($segments[0]).' AS '.$segments[1];
            } else {
                // consists of only alphanumerics so assume it's just a column
                if (ctype_alnum($segments[0])) {
                    $exprs[] = $this->columnName($segments[0], $table);
                } else {
                    $exprs[] = $segments[0];
                }
            }
        }
        return 'SELECT '.implode(', ', $exprs);
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    public function from(BaseStatement $statement): string
    {
        $expr = $statement->table;
        if ($statement->tableAlias !== null) {
            $expr.=' AS '.$statement->tableAlias;
        }
        return $expr;
    }

    /**
     * @param ConditionStatement $statement
     * @return string|null
     */
    public function where(ConditionStatement $statement): ?string
    {
        if ($statement->where !== null) {
            $exprs = [];
            foreach ($statement->where as $clause) {
                $exprs[] = $clause->toSql($this, $statement->tableAlias);
            }
            return 'WHERE '.implode(' AND ', $exprs);
        }
        return null;
    }

    /**
     * @param ConditionStatement $statement
     * @return string|null
     */
    public function order(ConditionStatement $statement): ?string
    {
        if ($statement->orderBy !== null) {
            $table = $statement->tableAlias ?? $statement->table;
            $exprs = [];
            foreach ($statement->orderBy as $column => $sort) {
                $exprs[] = $this->columnName($column, $table).' '.$sort;
            }
            return implode(', ', $exprs);
        }
        return null;
    }

    /**
     * @param ConditionStatement $statement
     * @return string|null
     */
    public function offset(ConditionStatement $statement): ?string
    {
        return $statement->offset !== null ? 'OFFSET '.$statement->offset : null;
    }

    /**
     * @param ConditionStatement $statement
     * @return string|null
     */
    public function limit(ConditionStatement $statement): ?string
    {
        return $statement->limit !== null ? 'LIMIT '.$statement->limit : null;
    }

    /**
     * @param string $name
     * @return string
     */
    public function tableName(string $name): string
    {
        return $this->addQuotes($name);
    }

    /**
     * @return string
     */
    public function bindName(): string
    {
        return '?';
    }

    /**
     * @param string $name
     * @param string|null $table
     * @return string
     */
    public function columnName(string $name, ?string $table = null): string
    {
        $name = $name !== '*' ? $this->addQuotes($name) : $name;
        return $table !== null ? $this->tableName($table).'.'.$name : $name;
    }

    /**
     * @param $value
     * @return string
     */
    public function parameter($value)
    {
        if ($value instanceof DateTimeInterface) {
            return '"'.$value->format(DateTimeInterface::RFC3339_EXTENDED).'"';
        }

        return $value;
    }

    /**
     * @param ConditionStatement $statement
     * @return array
     */
    protected function bindingsForCondition(ConditionStatement $statement): array
    {
        $bindings = [];
        foreach ($statement->where as $where) {
            foreach($where->getBindings() as $binding) {
                $bindings[] = $binding;
            }
        }
        return $bindings;
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
}
