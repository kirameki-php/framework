<?php

namespace Kirameki\Database\Query;

use Kirameki\Support\Concerns\Tappable;
use RuntimeException;
use Traversable;

class WhereClause
{
    use Tappable;

    protected Builder $builder;

    protected string $table;

    protected string $column;

    protected ?string $operator;

    protected bool $negated;

    protected $value;

    protected ?string $nextLogic;

    protected ?self $nextClause;

    /**
     * @param Builder $builder
     * @param string $column
     * @return static
     */
    public static function for(Builder $builder, string $column)
    {
        return (new static($builder))->column($column);
    }

    /**
     * @param Builder $builder
     * @param string $raw
     * @return static
     */
    public static function raw(Builder $builder, string $raw)
    {
        $instance = new static($builder);
        $instance->table = $builder->as ?? $builder->from;
        $instance->negated = false;
        $instance->operator = null;
        $instance->value = $raw;
        return $instance;
    }

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        $this->nextClause = clone $this->nextClause;
    }

    /**
     * @param string $column
     * @return static
     */
    public function and(string $column)
    {
        $this->nextLogic = 'AND';
        $this->nextClause = static::for($this->builder, $column);
        return $this->nextClause;
    }

    /**
     * @param string $column
     * @return static
     */
    public function or(string $column)
    {
        $this->nextLogic = 'OR';
        $this->nextClause = static::for($this->builder, $column);
        return $this->nextClause;
    }

    /**
     * @param string $column
     * @return $this
     */
    protected function column(string $column)
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @return $this
     */
    protected function negate()
    {
        $this->negated = true;
        return $this;
    }

    public function with(string $operator, $value)
    {
        if ($operator ===  '=') return $this->eq($value);
        if ($operator === '!=') return $this->ne($value);
        if ($operator === '<>') return $this->ne($value);
        if ($operator === '>' ) return $this->gt($value);
        if ($operator === '>=') return $this->gte($value);
        if ($operator === '<' ) return $this->lt($value);
        if ($operator === '<=') return $this->lte($value);
        if ($operator === 'IN') return $this->in($value);
        if ($operator === 'NOT IN') return $this->notIn($value);
        if ($operator === 'BETWEEN') return $this->between($value[0], $value[1]);
        if ($operator === 'NOT BETWEEN') return $this->notBetween($value[0], $value[1]);
        if ($operator === 'LIKE') return $this->like($value);
        if ($operator === 'NOT LIKE') return $this->notLike($value);
        throw new RuntimeException('Unknown operator:'.$operator);
    }

    /**
     * @return $this
     */
    public function eq($value)
    {
        $this->negated = false;
        $this->operator = '=';
        $this->value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function ne($value)
    {
        return $this->eq($value)->negate();
    }

    /**
     * @return $this
     */
    public function gte($value)
    {
        $this->negated = false;
        $this->operator = '>=';
        $this->value = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function gt($value)
    {
        $this->negated = false;
        $this->operator = '>';
        $this->value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function lte($value)
    {
        $this->negated = false;
        $this->operator = '<=';
        $this->value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function lt($value)
    {
        $this->negated = false;
        $this->operator = '<';
        $this->value = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function like(string $value)
    {
        $this->negated = false;
        $this->operator = 'LIKE';
        $this->value = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function notLike(string $value)
    {
        return $this->like($value)->negate();
    }

    /**
     * @param Builder|iterable $value
     * @return $this
     */
    public function in($value)
    {
        $this->negated = false;
        $this->operator = 'IN';
        if (is_iterable($value)) {
            $value = ($value instanceof Traversable) ? iterator_to_array($value) : (array) $value;
            $value = array_filter($value, static fn($s) => $s !== null);
            $value = array_unique($value);
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @param $values
     * @return $this
     */
    public function notIn($values)
    {
        return $this->in($values)->negate();
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     */
    public function between($min, $max)
    {
        $this->negated = false;
        $this->operator = 'BETWEEN';
        $this->value = [$min, $max];
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return static
     */
    public function notBetween($min, $max)
    {
        return $this->between($min, $max)->negate();
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        $values = is_array($this->value) ? $this->value : [$this->value];
        $formatter = $this->builder->getFormatter();
        $bindings = array_map(static fn($v) => $formatter->value($v), $values);

        if ($this->nextClause !== null) {
            $bindings = array_merge($bindings, $this->getBindings());
        }

        return $bindings;
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $sql = $this->buildCurrent();

        // Dig through all chained clauses if exists
        if ($this->nextClause !== null) {
            $nextLogic = $this->nextLogic;
            $nextClause = $this->nextClause;
            while ($nextClause !== null) {
                $sql.= $nextLogic.' '.$nextClause->buildCurrent();
                $nextLogic = $nextClause->nextLogic;
                $nextClause = $nextClause->nextClause;
            }
            $sql = '('.$sql.')';
        }

        return $sql;
    }

    /**
     * @return string
     */
    protected function buildCurrent(): string
    {
        $builder = $this->builder;
        $formatter = $builder->getFormatter();

        if ($this->column === null) {
            return (string) $this->value;
        }

        $column = "{{$formatter->column($this->column, $this->table)}";
        $operator = $this->operator;
        $negated = $this->negated;
        $value = $this->value;

        if ($operator === null) {
            return $column.' '.$value;
        }

        if ($operator === '=') {
            if ($value === null) {
                $expr = $negated ? 'IS NOT NULL' : 'IS NULL';
                return $column.' '.$expr;
            }
            $operator = $negated ? '!'.$operator : $operator;
            return $column.' '.$operator.' ?';
        }

        if ($operator === 'IN') {
            if (empty($value)) return '1 = 0';
            $operator = $negated ? 'NOT '.$operator : $operator;
            $expr = implode(',', array_fill(0, count($value), '?'));
            return $column.' '.$operator.' ('.$expr.')';
        }

        if ($operator === 'BETWEEN') {
            $operator = $negated ? 'NOT '.$operator : $operator;
            return $column.' '.$operator.' ? AND ?';
        }

        if ($operator === 'LIKE') {
            $operator = $negated ? 'NOT '.$operator : $operator;
            return $column.' '.$operator.' ?';
        }

        // ">=", ">", "<", "<=" and raw cannot be negated
        if ($negated) {
            // TODO needs better exception
            throw new RuntimeException("Negation not valid for '$operator'");
        }

        return $column.' '.$operator.' '.$value;
    }
}
