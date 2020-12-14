<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Support\Collection;

class SelectBuilder extends ConditonsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new SelectStatement;
    }

    /**
     * @param string $table
     * @param string|null $as
     * @return $this
     */
    public function from(string $table, ?string $as = null): static
    {
        return $this->table($table, $as);
    }

    /**
     * @param mixed ...$columns
     * @return $this
     */
    public function columns(...$columns): static
    {
        $this->statement->columns = $columns;
        return $this;
    }

    /**
     * @return $this
     */
    public function distinct(): static
    {
        $this->statement->distinct = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function lock(): static
    {
        $this->statement->lock = true;
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function groupBy(string ...$columns): static
    {
        $this->statement->groupBy = $columns;
        return $this;
    }

    /**
     * @param string|ConditionBuilder $column
     * @param mixed $operator
     * @param mixed|null $value
     * @return $this
     */
    public function having($column, $operator, $value = null): static
    {
        $this->addHavingCondition($this->buildCondition(...func_get_args()));
        return $this;
    }

    /**
     * @param int $skipRows
     * @return $this
     */
    public function offset(int $skipRows): static
    {
        $this->statement->offset = $skipRows;
        return $this;
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return new Collection($this->execSelect());
    }

    /**
     * @return array|null
     */
    public function one(): ?array
    {
        return $this->copy()->limit(1)->execSelect()[0] ?? null;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->copy()->columns(1)->limit(1)->execSelect());
    }

    /**
     * @return array|int
     */
    public function count(): array|int
    {
        // If GROUP BY exists but no SELECT is defined, use the first GROUP BY column that was defined.
        if ($this->statement->groupBy !== null &&
            $this->statement->columns === null) {
            $this->addToSelect(current($this->statement->groupBy));
        }

        $results = $this->copy()->addToSelect('count(*) AS total')->execSelect();

        // when GROUP BY is defined, return in [columnValue => count] format
        if ($this->statement->groupBy !== null) {
            $key = array_key_first($this->statement->columns);
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
     * @return int|float
     */
    public function sum(string $column): float|int
    {
        return $this->execAggregate($column, 'SUM');
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function avg(string $column): float|int
    {
        return $this->execAggregate($column, 'AVG');
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function min(string $column)
    {
        return $this->execAggregate($column, 'MIN');
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function max(string $column)
    {
        return $this->execAggregate($column, 'MAX');
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
     * @return array
     */
    public function inspect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->selectStatement($this->statement);
        $bindings = $formatter->selectBindings($this->statement);
        return compact('statement', 'bindings');
    }

    /**
     * @param string $select
     * @return $this
     */
    protected function addToSelect(string $select): static
    {
        $this->statement->columns[] = $select;
        return $this;
    }

    /**
     * @param ConditionDefinition $condition
     * @return $this
     */
    protected function addHavingCondition(ConditionDefinition $condition): static
    {
        $this->statement->having ??= [];
        $this->statement->having[] = $condition;
        return $this;
    }

    /**
     * @return array
     */
    protected function execSelect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->selectStatement($this->statement);
        $bindings = $formatter->selectBindings($this->statement);
        return $this->connection->query($statement, $bindings);
    }

    /**
     * @param string $function
     * @param string $column
     * @return int
     */
    protected function execAggregate(string $function, string $column): int
    {
        $formatter = $this->connection->getQueryFormatter();
        $column = $formatter->columnName($column);
        $results = $this->copy()->columns($function.'('.$column.') AS cnt')->execSelect();
        return !empty($results) ? $results[0]['cnt'] : 0;
    }
}
