<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Range;
use Kirameki\Database\Query\Statements\ConditionalStatement;
use Kirameki\Database\Query\Condition;
use RuntimeException;

abstract class ConditonBuilder extends Builder
{
    /**
     * @var ConditionalStatement
     */
    protected $statement;

    /**
     * @param $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        return $this->addWhereCondition($this->buildCondition(...func_get_args()));
    }

    /**
     * @param $column
     * @param mixed|null $value
     * @return $this
     */
    public function whereNot($column, $value)
    {
        return $this->addWhereCondition($this->buildNotCondition($column, $value));
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function whereRaw(string $raw)
    {
        return $this->addWhereCondition(Condition::raw($raw));
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
     * @param $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return Condition
     */
    protected function buildCondition($column, $operator = null, $value = null): Condition
    {
        $num = func_num_args();

        if ($num === 1 && ($column instanceof Condition)) {
            return $column;
        }

        if ($num === 2) {
            if (is_callable($operator)) return Condition::for($column)->tap($operator);
            if (is_iterable($operator)) return Condition::for($column)->in($operator);
            if ($operator instanceof Range) return Condition::for($column)->inRange($operator);
            return Condition::for($column)->equals($operator);
        }

        if ($num === 3) {
            return Condition::for($column)->with($operator, $value);
        }

        throw new RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

    /**
     * @param $column
     * @param mixed|null $value
     * @return Condition
     */
    protected function buildNotCondition(string $column, $value): Condition
    {
        if (is_array($value)) return Condition::for($column)->notIn($value);
        if ($value instanceof Range) return Condition::for($column)->notInRange($value);
        return Condition::for($column)->notEquals($value);
    }

    /**
     * @param Condition $condition
     * @return $this
     */
    protected function addWhereCondition(Condition $condition)
    {
        $this->statement->where ??= [];
        $this->statement->where[] = $condition;
        return $this;
    }
}
