<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Expressions\Expr;
use Kirameki\Database\Query\Expressions\Raw;
use Kirameki\Support\Collection;
use function is_array;

/**
 * @property SelectStatement $statement
 */
class SelectBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new SelectStatement();
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
     * @param string|Expr ...$columns
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
    public function forShare(): static
    {
        $this->statement->lockType = LockType::Shared;
        return $this;
    }

    /**
     * @param LockOption|null $option
     * @return $this
     */
    public function forUpdate(LockOption $option = null): static
    {
        $this->statement->lockType = LockType::Exclusive;
        $this->statement->lockOption = $option;
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
    public function having(ConditionBuilder|string $column, mixed $operator, mixed $value = null): static
    {
        $this->addHavingCondition($this->buildCondition(...func_get_args())->getDefinition());
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
     * @return Collection<int, mixed>
     */
    public function all(): Collection
    {
        return new Collection($this->execute());
    }

    /**
     * @return mixed|null
     */
    public function one(): mixed
    {
        return $this->copy()->limit(1)->execute()[0] ?? null;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->copy()->columns("1")->limit(1)->execute()->isNotEmpty();
    }

    /**
     * @return array<int>|int
     */
    public function count(): array|int
    {
        $statement = $this->statement;

        // If GROUP BY exists but no SELECT is defined, use the first GROUP BY column that was defined.
        if ($statement->columns === null && is_array($statement->groupBy)) {
            $this->addToSelect($statement->groupBy[0]);
        }

        /** @var array<array<string|int>> $results */
        $results = $this->copy()->addToSelect(new Raw('count(*) AS total'))->execute();

        // when GROUP BY is defined, return in [columnValue => count] format
        if (is_array($statement->groupBy)) {
            $keyName = $statement->groupBy[0];
            $aggregated = [];
            foreach ($results as $result) {
                $groupKey = $result[$keyName];
                $groupTotal = (int) $result['total'];
                $aggregated[$groupKey] = $groupTotal;
            }
            return $aggregated;
        }

        if (empty($results)) {
            return 0;
        }

        return (int) $results[0]['total'];
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function sum(string $column): float|int
    {
        return $this->aggregate($column, 'SUM');
    }

    /**
     * @param string $column
     * @return int|float
     */
    public function avg(string $column): float|int
    {
        return $this->aggregate($column, 'AVG');
    }

    /**
     * @param string $column
     * @return int
     */
    public function min(string $column): int
    {
        return $this->aggregate($column, 'MIN');
    }

    /**
     * @param string $column
     * @return int
     */
    public function max(string $column): int
    {
        return $this->aggregate($column, 'MAX');
    }

    /**
     * @param string|Expr $select
     * @return $this
     */
    protected function addToSelect(string|Expr $select): static
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
        $statement = $this->statement;
        $statement->having ??= [];
        $statement->having[] = $condition;
        return $this;
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->getQueryFormatter()->formatSelectStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->getBindingsForSelect($this->statement);
    }

    /**
     * @param string $function
     * @param string $column
     * @return int
     */
    protected function aggregate(string $function, string $column): int
    {
        $column = $this->getQueryFormatter()->columnize($column);
        $select = new Raw("$function($column) AS aggregate");
        /** @var array{ aggregate: int } $results */
        $results = $this->copy()->columns($select)->execute()->first() ?? ['aggregate' => 0];
        return $results['aggregate'];
    }
}
