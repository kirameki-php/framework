<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Statements\ConditionStatement;
use Kirameki\Database\Query\WhereClause;
use RuntimeException;

abstract class ConditonBuilder extends Builder
{
    /**
     * @var ConditionStatement
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
     * @param WhereClause $clause
     * @return $this
     */
    protected function addWhereClause(WhereClause $clause)
    {
        $this->statement->where ??= [];
        $this->statement->where[] = $clause;
        return $this;
    }
}
