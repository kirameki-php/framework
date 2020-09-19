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
        $this->statement = new Statement($connection->getFormatter());
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
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
        $this->statement->orderBy = null;
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
    public function all(): Collection
    {
        return new Collection($this->runQuery());
    }

    /**
     * @return array
     */
    public function one()
    {
        return $this->copy()->limit(1)->runQuery();
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->copy()->select(1)->limit(1)->runQuery());
    }

    /**
     * @return array|int
     */
    public function count()
    {
        // If GROUP BY exists but no SELECT is defined, use the first GROUP BY column that was defined.
        if ($this->statement->groupBy !== null &&
            $this->statement->select === null) {
            $this->addToSelect(current($this->statement->groupBy));
        }

        $results = $this->copy()->addToSelect('count(*) as cnt')->runQuery();

        // when GROUP BY is defined, return in [colmnValue => count] format
        if ($this->statement->groupBy !== null) {
            $key = array_key_first($this->statement->select);
            $aggregated = [];
            foreach ($results as $result) {
                $result[$key] = $result['cnt'];
            }
            return $aggregated;
        }

        if (empty($results)) {
            return 0;
        }

        return $results[0]['cnt'];
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        $formatter = $this->connection->getFormatter();
        $bindings = [];
        foreach ($this->statement->where as $where) {
            foreach($where->getBindings($formatter) as $binding) {
                $bindings[] = $binding;
            }
        }
        return $bindings;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->connection->getFormatter()->intropolate(
            (string) $this->statement,
            $this->getBindings()
        );
    }

    /**
     * @return static
     */
    public function subquery()
    {
        return new static($this->connection);
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
     * @param string $select
     * @return $this
     */
    protected function addToSelect(string $select)
    {
        $this->statement->select[] = $select;
        return $this;
    }

    /**
     * @return static
     */
    protected function copy()
    {
        return clone $this;
    }

    /**
     * @return array
     */
    protected function runQuery(): array
    {
        return $this->connection->query(
            (string) $this->statement,
            $this->getBindings()
        );
    }
}
