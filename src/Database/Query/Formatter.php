<?php

namespace Kirameki\Database\Query;

use DateTimeInterface;
use Kirameki\Database\Connection\Connection;

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
     * @param Statement $statement
     * @return string
     */
    public function statementForSelect(Statement $statement): string
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
     * @param Statement $statement
     * @param iterable $list
     * @return string
     */
    public function statementForBulkInsert(Statement $statement, iterable $list): string
    {
        $columnsAssoc = [];
        $listSize = 0;
        foreach ($list as $each) {
            foreach($each as $name => $value) {
                if ($value !== null) {
                    $columnsAssoc[$name] = null;
                }
            }
            $listSize++;
        }

        $columnNames = array_keys($columnsAssoc);
        $cloumnCount = count($columnNames);

        $valuesList = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $cloumnCount; $j++) {
                $binders[] = $this->bindName();
            }
            $valuesList[] = '('.implode(', ', $binders).')';
        }

        return implode(' ', [
            'INSERT INTO',
            $this->tableName($statement->table),
            '('.implode(', ', $columnNames).')',
            'VALUES',
            implode(', ', $valuesList),
        ]);
    }

    /**
     * @param Statement $statement
     * @param array $values
     * @return string
     */
    public function statementForUpdate(Statement $statement, array $values): string
    {
        $assignments = [];
        foreach (array_keys($values) as $name) {
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
     * @param Statement $statement
     * @return string
     */
    public function statementForDelete(Statement $statement): string
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
     * @param Statement $statement
     * @return string
     */
    public function select(Statement $statement): string
    {
        if (empty($statement->select)) {
            $statement->select[] = '*';
        }
        $exprs = [];
        $table = $statement->tableAlias ?? $statement->table;
        foreach ($statement->select as $name) {
            $segments = preg_split('/\s+as\s+/i', $name);
            if (count($segments) > 1) {
                $exprs[] = $this->columnName($segments[0]).' AS '.$segments[1];
            } else {
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
     * @param Statement $statement
     * @return string
     */
    public function from(Statement $statement): string
    {
        $expr = $statement->table;
        if ($statement->tableAlias !== null) {
            $expr.=' AS '.$statement->tableAlias;
        }
        return $expr;
    }

    /**
     * @param Statement $statement
     * @return string|null
     */
    public function where(Statement $statement): ?string
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
     * @param Statement $statement
     * @return string|null
     */
    public function order(Statement $statement): ?string
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
     * @param Statement $statement
     * @return string|null
     */
    public function offset(Statement $statement): ?string
    {
        return $statement->offset !== null ? 'OFFSET '.$statement->offset : null;
    }

    /**
     * @param Statement $statement
     * @return string|null
     */
    public function limit(Statement $statement): ?string
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
