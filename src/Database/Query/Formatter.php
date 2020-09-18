<?php

namespace Kirameki\Database\Query;

use DateTimeInterface;
use Kirameki\Database\Connection\Connection;

class Formatter
{
    protected Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function select(Statement $statement): string
    {
        if (empty($statement->select)) {
            $statement->select = ['*'];
        }
        return implode(', ', $statement->select);
    }

    public function from(Statement $statement): string
    {
        $expr = $statement->from;
        if ($statement->as !== null) {
            $expr.=' AS '.$statement->as;
        }
        return $expr;
    }

    public function where(Statement $statement): ?string
    {
        if ($statement->where !== null) {
            $exprs = [];
            foreach ($statement->where as $clause) {
                $exprs[] = $clause->toSql($this, $statement->as);
            }
            return 'WHERE '.implode(' AND ', $exprs);
        }
        return null;
    }

    public function order(Statement $statement): ?string
    {
        if ($statement->orderBy !== null) {
            $table = $statement->as ?? $statement->from;
            $exprs = [];
            foreach ($statement->orderBy as $column => $sort) {
                $exprs[] = $this->column($column, $table).' '.$sort;
            }
            return implode(', ', $exprs);
        }
        return null;
    }

    public function offset(Statement $statement): ?string
    {
        return $statement->offset !== null ? 'OFFSET '.$statement->offset : null;
    }

    public function limit(Statement $statement): ?string
    {
        return $statement->limit !== null ? 'LIMIT '.$statement->limit : null;
    }

    /**
     * @param string $name
     * @return string
     */
    public function table(string $name): string
    {
        return "`$name`";
    }

    /**
     * @param string $name
     * @param string|null $table
     * @return string
     */
    public function column(string $name, ?string $table = null): string
    {
        $name = $name !== '*' ? "`$name`" : $name;
        return $table !== null ? $this->table($table).'.'.$name : $name;
    }

    /**
     * @param $value
     * @return array|string
     */
    public function value($value)
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_string($value)) {
            $quoted = $this->connection->getPdo()->quote($value);
            return is_string($quoted) ? $quoted : $value;
        }

        if (is_iterable($value)) {
            $formatted = [];
            foreach ($value as $k => $v) {
                $formatted[$k] = $this->value($v);
            }
            return $formatted;
        }

        if ($value instanceof DateTimeInterface) {
            return '"'.$value->format(DateTimeInterface::RFC3339_EXTENDED).'"';
        }

        return $value;
    }
}
