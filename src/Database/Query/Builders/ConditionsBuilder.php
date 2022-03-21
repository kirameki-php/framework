<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Closure;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Support\SortOrder;
use RuntimeException;
use Webmozart\Assert\Assert;
use function is_string;

/**
 * @property ConditionsStatement $statement
 */
abstract class ConditionsBuilder extends StatementBuilder
{
    /**
     * @var ConditionBuilder|null
     */
    protected ConditionBuilder|null $lastCondition = null;

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function where(mixed ...$args): static
    {
        Assert::countBetween($args, 1, 3);
        $this->lastCondition = $this->buildCondition(...$args);
        return $this->addWhereCondition($this->lastCondition->getDefinition());
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function whereNot(mixed ...$args): static
    {
        // only 2 because operators are not supported for NOT's
        Assert::countBetween($args, 1, 2);
        $this->lastCondition = $this->buildCondition(...$args)->negate();
        return $this->addWhereCondition($this->lastCondition->getDefinition());
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function whereRaw(string $raw): static
    {
        return $this->addWhereCondition(ConditionBuilder::raw($raw)->getDefinition());
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function and(mixed ...$args): static
    {
        if ($this->lastCondition?->and()->apply($this->buildCondition(...$args)) !== null) {
            return $this;
        }
        throw new RuntimeException('and called without a previous condition. Define a where before declaring and');
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function or(mixed ...$args): static
    {
        if ($this->lastCondition?->or()->apply($this->buildCondition(...$args)) !== null) {
            return $this;
        }
        throw new RuntimeException('or called without a previous condition. Define a where before declaring or');
    }

    /**
     * @param string $column
     * @param SortOrder $sort
     * @return $this
     */
    public function orderBy(string $column, SortOrder $sort = SortOrder::Ascending): static
    {
        $this->statement->orderBy ??= [];
        $this->statement->orderBy[$column] = $sort;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByAsc(string $column): static
    {
        return $this->orderBy($column);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, SortOrder::Descending);
    }

    /**
     * @return $this
     */
    public function reorder(): static
    {
        $this->statement->orderBy = null;
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count): static
    {
        $this->statement->limit = $count;
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return ConditionBuilder
     */
    protected function buildCondition(mixed ...$args): ConditionBuilder
    {
        $num = func_num_args();

        if ($num === 1) {
            $condition = $args[0];
            if ($condition instanceof Closure) {
                $query = new SelectBuilder($this->getConnection());
                return ConditionBuilder::nest($condition($query) ?? $query);
            }
            if ($condition instanceof SelectBuilder) {
                return ConditionBuilder::nest($condition);
            }
            if ($condition instanceof ConditionBuilder) {
                return $condition;
            }
        }

        if ($num === 2 && is_string($args[0])) {
            $column = $args[0];
            $value = $args[1];

            if ($value instanceof Closure) {
                return ConditionBuilder::for($column)->tap($value);
            }

            if ($value instanceof Range) {
                return ConditionBuilder::for($column)->inRange($value);
            }

            if (is_iterable($value)) {
                return ConditionBuilder::for($column)->in($value);
            }

            return ConditionBuilder::for($column)->equals($value);
        }

        if ($num === 3 && is_string($args[0]) && is_string($args[1])) {
            $column = $args[0];
            $operator = $args[1];
            $value = $args[2];
            return ConditionBuilder::for($column)->match($operator, $value);
        }

        throw new RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

    /**
     * @param ConditionDefinition $definition
     * @return $this
     */
    protected function addWhereCondition(ConditionDefinition $definition): static
    {
        $this->statement->where ??= [];
        $this->statement->where[] = $definition;
        return $this;
    }
}
