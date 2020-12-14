<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use RuntimeException;

abstract class ConditonsBuilder extends StatementBuilder
{
    /**
     * @var ConditionsStatement
     */
    protected $statement;

    /**
     * @param string|ConditionBuilder $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null): static
    {
        return $this->addWhereCondition($this->buildCondition(...func_get_args()));
    }

    /**
     * @param $column
     * @param mixed|null $value
     * @return $this
     */
    public function whereNot($column, $value): static
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
     * @param string|array $column
     * @param string $sort
     * @return $this
     */
    public function orderBy($column, string $sort = 'ASC'): static
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
    public function orderByAsc(string $column): static
    {
        return $this->orderBy($column, 'ASC');
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
    protected function buildCondition($column, $operator = null, $value = null): ConditionDefinition
    {
        $num = func_num_args();
        if ($num === 1) {
            return $column->getDefinition();
        }
        if ($num === 2) {
            if (is_callable($operator)) return ConditionBuilder::for($column)->tap($operator)->getDefinition();
            if (is_iterable($operator)) return ConditionBuilder::for($column)->in($operator)->getDefinition();
            if ($operator instanceof Range) return ConditionBuilder::for($column)->inRange($operator)->getDefinition();
            return ConditionBuilder::for($column)->equals($operator)->getDefinition();
        }
        if ($num === 3) {
            return ConditionBuilder::for($column)->with($operator, $value)->getDefinition();
        }

        throw new RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

    /**
     * @param $column
     * @param mixed|null $value
     * @return ConditionDefinition
     */
    protected function buildNotCondition(string $column, $value): ConditionDefinition
    {
        if (is_array($value)) return ConditionBuilder::for($column)->notIn($value)->getDefinition();
        if ($value instanceof Range) return ConditionBuilder::for($column)->notInRange($value)->getDefinition();
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
