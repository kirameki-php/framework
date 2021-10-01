<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Support\Range;
use RuntimeException;

/**
 * @property-read ConditionsStatement $statement
 */
abstract class ConditionsBuilder extends StatementBuilder
{
    /**
     * @param string|ConditionBuilder $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where(ConditionBuilder|string $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->addWhereCondition($this->buildCondition(...func_get_args()));
    }

    /**
     * @param $column
     * @param mixed|null $value
     * @return $this
     */
    public function whereNot($column, mixed $value): static
    {
        return $this->addWhereCondition($this->buildNotCondition($column, $value));
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
     * @param string $column
     * @param string $sort
     * @return $this
     */
    public function orderBy(string $column, string $sort = 'ASC'): static
    {
        $sort = strtoupper($sort);
        if (! in_array($sort, ['ASC', 'DESC'])) {
            throw new RuntimeException('Invalid sorting: '.$sort. ' Only ASC or DESC is allowed.');
        }

        if ($this->statement instanceof ConditionsStatement) {
            $this->statement->orderBy ??= [];
            $this->statement->orderBy[$column] = $sort;
        } else {
            $table = $this->statement->table;
            $class = get_class($this->statement);
            throw new RuntimeException("Invalid statement orderBy applied to $class for $table->$column.");
        }

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
        return $this->orderBy($column, 'DESC');
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
     * @param string|ConditionBuilder $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return ConditionDefinition
     */
    protected function buildCondition(ConditionBuilder|string $column, mixed $operator = null, mixed $value = null): ConditionDefinition
    {
        $num = func_num_args();

        if ($num === 1) {
            return $column->getDefinition();
        }

        if ($num === 2) {
            if (is_callable($operator)) {
                return ConditionBuilder::for($column)->tap($operator)->getDefinition();
            }

            if (is_iterable($operator)) {
                return ConditionBuilder::for($column)->in($operator)->getDefinition();
            }

            if ($operator instanceof Range) {
                return ConditionBuilder::for($column)->inRange($operator)->getDefinition();
            }

            return ConditionBuilder::for($column)->equals($operator)->getDefinition();
        }

        if ($num === 3) {
            return ConditionBuilder::for($column)->with($operator, $value)->getDefinition();
        }

        throw new RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

    /**
     * @param string $column
     * @param mixed|null $value
     * @return ConditionDefinition
     */
    protected function buildNotCondition(string $column, mixed $value): ConditionDefinition
    {
        if (is_array($value)) {
            return ConditionBuilder::for($column)->notIn($value)->getDefinition();
        }

        if ($value instanceof Range) {
            return ConditionBuilder::for($column)->notInRange($value)->getDefinition();
        }

        return ConditionBuilder::for($column)->notEquals($value)->getDefinition();
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
