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
        $this->statement = new Statement($connection->getQueryFormatter());
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

        throw new RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
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
        return new Collection($this->execute());
    }

    /**
     * @return array
     */
    public function one(): array
    {
        return $this->copy()->limit(1)->execute();
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->copy()->select(1)->limit(1)->execute());
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

        $results = $this->copy()->addToSelect('count(*) AS total')->execute();

        // when GROUP BY is defined, return in [columnValue => count] format
        if ($this->statement->groupBy !== null) {
            $key = array_key_first($this->statement->select);
            $aggregated = [];
            foreach ($results as $result) {
                $result[$key] = $result['total'];
            }
            return $aggregated;
        }

        if (empty($results)) {
            return 0;
        }

        return $results[0]['total'];
    }

    /**
     * @param string $column
     * @return int
     */
    public function sum(string $column)
    {
        $formatter = $this->connection->getQueryFormatter();
        $column = $formatter->column($column);
        $results = $this->copy()->select('SUM('.$column.') as total')->execute();
        return !empty($results) ? $results[0]['total'] : 0;
    }

    /**
     * @param string $column
     * @return int
     */
    public function avg(string $column)
    {
        $formatter = $this->connection->getQueryFormatter();
        $column = $formatter->column($column);
        $results = $this->copy()->select('AVG('.$column.') as total')->execute();
        return !empty($results) ? $results[0]['total'] : 0;
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        $bindings = [];
        foreach ($this->statement->where as $where) {
            foreach($where->getBindings() as $binding) {
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
        return $this->connection->getQueryFormatter()->interpolate(
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
    protected function execute(): array
    {
        return $this->connection->execute(
            (string) $this->statement,
            $this->getBindings()
        );
    }
}
