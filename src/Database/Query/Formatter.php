<?php

namespace Kirameki\Database\Query;

use DateTimeInterface;
use PDO;

class Formatter
{
    protected PDO $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
            $quoted = $this->pdo->quote($value);
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

    public function order(array $orders, ?string $table = null)
    {
        $exprs = [];
        foreach ($orders as $column => $sort) {
            $exprs[] = $this->column($column, $table).' '.$sort;
        }
        return implode(', ', $exprs);
    }
}
