<?php

namespace Kirameki\Database\Query;

use Kirameki\Database\Connection\Connection;
use Kirameki\Support\Collection;
use RuntimeException;

class Builder
{
    protected Connection $connection;

    protected Statement $statement;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new Statement();
    }

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @param string $table
     * @param string|null $as
     * @return $this
     */
    public function from(string $table, ?string $as = null)
    {
        $this->statement->from = $table;
        $this->statement->as = $as;
        return $this;
    }

    /**
     * @param mixed ...$columns
     * @return $this
     */
    public function select(...$columns)
    {
        $this->statement->select = $columns;
        return $this;
    }

    /**
     * @return $this
     */
    public function distinct()
    {
        $this->statement->distinct = true;
        return $this;
    }

    /**
     * @param $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        $num = func_num_args();

        if ($num === 1) {
            return is_callable($column)
                ? $this->addWhereClause(WhereClause::for($column)->tap($column))
                : $this->addWhereClause($column);
        }

        if ($num === 2) {
            return is_array($operator)
                ? $this->addWhereClause(WhereClause::for($column)->in($operator))
                : $this->addWhereClause(WhereClause::for($column)->eq($operator));
        }

        if ($num === 3) {
            return $this->addWhereClause(WhereClause::for($column)->with($operator, $value));
        }

        throw new \RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function whereRaw(string $raw)
    {
        return $this->addWhereClause(WhereClause::raw($raw));
    }

    /**
     * @param string|array $column
     * @param string $sort
     * @return $this
     */
    public function orderBy($column, string $sort = 'ASC')
    {
        if (is_array($column)) {
            foreach ($column as $c => $s) {
                $this->orderBy($c, $s);
            }
            return $this;
        }

        $sort = strtoupper($sort);
        if (! in_array($sort, ['ASC', 'DESC'])) {
            throw new RuntimeException('Invalid sorting: '.$sort. ' Only ASC or DESC is allowed.');
        }
        $this->statement->orderBy ??= [];
        $this->statement->orderBy[$column] = $sort;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByAsc(string $column)
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column)
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * @return $this
     */
    public function reorder()
    {
        $this->statement->order = null;
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count)
    {
        $this->statement->limit = $count;
        return $this;
    }

    /**
     * @param int $skipRows
     * @return $this
     */
    public function offset(int $skipRows)
    {
        $this->statement->offset = $skipRows;
        return $this;
    }

    /**
     * @return $this
     */
    public function lock()
    {
        $this->statement->lock = true;
        return $this;
    }

    /**
     * @return Collection
     */
    public function all()
    {
        $results = $this->connection->select($this->toSql(), $this->getBindings());
        return new Collection($results);
    }

    /**
     * @return array|int
     */
    public function count(): int|array
    {
        return $this->select('count(*) as cnt')->all();
    }

    /**
     * @param WhereClause $clause
     * @return $this
     */
    protected function addWhereClause(WhereClause $clause)
    {
        $this->statement->where ??= [];
        $this->statement->where[] = $clause;
        return $this;
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        $formatter = $this->connection->getFormatter();
        $table = $this->statement->as ?? $this->statement->from;

        $bindings = [];
        foreach ($this->statement->where as $where) {
            $whereClauses[] = $where->toSql($formatter, $table);
            foreach($where->getBindings($formatter) as $binding) {
                $bindings[] = $binding;
            }
        }
        return $bindings;
    }

    /**
     * @param false $bind
     * @return string
     */
    public function toSql($bind = false): string
    {
        $statement = $this->statement;
        $formatter = $this->connection->getFormatter();

        $sql = implode(' ', [
            $formatter->select($statement),
            $formatter->from($statement),
            $formatter->where($statement),
            $formatter->offset($statement),
            $formatter->limit($statement),
            $formatter->order($statement),
        ]);

        if ($bind) {
            $bindings = $this->getBindings();
            $formatter->intropolate($sql, $bindings);
        }

        return $sql;
    }
}
