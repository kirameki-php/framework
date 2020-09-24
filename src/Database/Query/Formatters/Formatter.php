<?php

namespace Kirameki\Database\Query\Formatters;

use DateTimeInterface;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Expr;
use Kirameki\Database\Query\Statements\BaseStatement;
use Kirameki\Database\Query\Statements\ConditionalStatement;
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
        return implode(' ', array_filter([
            $this->select($statement),
            $this->from($statement),
            $this->conditions($statement),
        ]));
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
            $this->conditions($statement),
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
            $this->conditions($statement),
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
        return preg_replace_callback('/\?\??/', function($matches) use (&$bindings) {
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

        $distinct = '';
        if ($statement->distinct) {
            $distinct = 'DISTINCT ';
        }

        foreach ($statement->columns as $name) {
            if ($name instanceof Expr) {
                $exprs[] = $name->toString();
                continue;
            }

            $segments = preg_split('/\s+as\s+/i', $name);
            if (count($segments) > 1) {
                $exprs[] = $this->columnName($segments[0]).' AS '.$segments[1];
                continue;
            }

            // consists of only alphanumerics so assume it's just a column
            if (ctype_alnum($segments[0])) {
                $exprs[] = $this->columnName($segments[0], $statement->tableAlias);
                continue;
            }

            $exprs[] = $segments[0];
        }
        return 'SELECT '.$distinct.implode(', ', $exprs);
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    public function from(BaseStatement $statement): string
    {
        if (!isset($statement->table)) {
            return '';
        }
        $expr = $this->tableName($statement->table);
        if ($statement->tableAlias !== null) {
            $expr.=' AS '.$statement->tableAlias;
        }
        return 'FROM '.$expr;
    }

    /**
     * @param ConditionalStatement $statement
     * @return string
     */
    public function conditions(ConditionalStatement $statement): string
    {
        $parts = [];
        if ($statement->where !== null) {
            $clause = [];
            foreach ($statement->where as $condition) {
                $clause[] = $condition->toSql($this, $statement->tableAlias);
            }
            $parts[]= 'WHERE '.implode(' AND ', $clause);
        }
        if ($statement instanceof SelectStatement) {
            if ($statement->groupBy !== null) {
                $clause = [];
                foreach ($statement->groupBy as $column) {
                    $clause[] = $this->columnName($column, $statement->tableAlias);
                }
                $parts[]= 'GROUP BY '.implode(', ', $clause);
            }
        }
        if ($statement->orderBy !== null) {
            $clause = [];
            foreach ($statement->orderBy as $column => $sort) {
                $clause[] = $this->columnName($column, $statement->tableAlias).' '.$sort;
            }
            $parts[]= 'ORDER BY '.implode(', ', $clause);
        }
        if ($statement->limit !== null) {
            $parts[]= 'LIMIT '.$statement->limit;
        }
        if ($statement->offset !== null) {
            $parts[]= 'OFFSET '.$statement->offset;
        }
        return implode(' ', $parts);
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
     * @param ConditionalStatement $statement
     * @return array
     */
    protected function bindingsForCondition(ConditionalStatement $statement): array
    {
        $bindings = [];
        if ($statement->where !== null) {
            foreach ($statement->where as $where) {
                foreach($where->getBindings() as $binding) {
                    $bindings[] = $binding;
                }
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
