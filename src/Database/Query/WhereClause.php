<?php

namespace Kirameki\Database\Query;

use Kirameki\Database\Query\Builders\Builder;
use Kirameki\Support\Concerns\Tappable;
use RuntimeException;
use Traversable;

class WhereClause
{
    use Tappable;

    protected ?string $column;

    protected ?string $operator;

    protected bool $negated;

    protected $parameters;

    protected ?string $nextLogic;

    protected ?self $nextClause;

    /**
     * @param string $column
     * @return static
     */
    public static function for(string $column)
    {
        return new static($column);
    }

    /**
     * @param string $column
     * @return static
     */
    public static function not(string $column)
    {
        return (new static($column))->negate();
    }

    /**
     * @param string $raw
     * @return static
     */
    public static function raw(string $raw)
    {
        $instance = new static();
        $instance->parameter($raw);
        return $instance;
    }

    /**
     * @param string|null $column
     */
    public function __construct(string $column = null)
    {
        $this->column = $column;
        $this->negated = false;
        $this->operator = null;
        $this->nextLogic = null;
        $this->nextClause = null;
    }

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        if ($this->nextClause !== null) {
            $this->nextClause = clone $this->nextClause;
        }
    }

    /**
     * @param string $column
     * @return static
     */
    public function and(string $column)
    {
        $this->nextLogic = 'AND';
        $this->nextClause = static::for($column);
        return $this->nextClause;
    }

    /**
     * @param string $column
     * @return static
     */
    public function or(string $column)
    {
        $this->nextLogic = 'OR';
        $this->nextClause = static::for($column);
        return $this->nextClause;
    }

    /**
     * @return $this
     */
    protected function negate()
    {
        $this->negated = true;
        return $this;
    }

    /**
     * @param string $operator
     * @param $value
     * @return $this
     */
    public function with(string $operator, $value)
    {
        if ($operator === '=') return $this->eq($value);
        if ($operator === '!=') return $this->ne($value);
        if ($operator === '<>') return $this->ne($value);
        if ($operator === '>') return $this->gt($value);
        if ($operator === '>=') return $this->gte($value);
        if ($operator === '<') return $this->lt($value);
        if ($operator === '<=') return $this->lte($value);
        if ($operator === 'IN') return $this->in($value);
        if ($operator === 'NOT IN') return $this->notIn($value);
        if ($operator === 'BETWEEN') return $this->between($value[0], $value[1]);
        if ($operator === 'NOT BETWEEN') return $this->notBetween($value[0], $value[1]);
        if ($operator === 'LIKE') return $this->like($value);
        if ($operator === 'NOT LIKE') return $this->notLike($value);
        throw new RuntimeException('Unknown operator:' . $operator);
    }

    /**
     * @return $this
     */
    public function eq($value)
    {
        $this->negated = false;
        $this->operator = '=';
        $this->parameter($value);
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
        $this->parameter($value);
        return $this;
    }

    /**
     * @return $this
     */
    public function gt($value)
    {
        $this->negated = false;
        $this->operator = '>';
        $this->parameter($value);
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
        $this->parameter($value);
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
        $this->parameter($value);
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
        $this->parameter($value);
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
            $value = ($value instanceof Traversable) ? iterator_to_array($value) : (array)$value;
            $value = array_filter($value, static fn($s) => $s !== null);
            $value = array_unique($value);
        }
        $this->parameter($value);
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
        $this->parameters = [$min, $max];
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     */
    public function notBetween($min, $max)
    {
        return $this->between($min, $max)->negate();
    }

    /**
     * @param Range $range
     * @return $this
     */
    public function inRange(Range $range)
    {
        $this->negated = false;
        $this->operator = 'RANGE';
        $this->parameters = $range;
        return $this;
    }

    /**
     * @param Range $range
     * @return $this
     */
    public function notInRange(Range $range)
    {
        return $this->inRange($range)->negate();
    }

    /**
     * @param array|mixed $value
     * @return $this
     */
    public function parameter($value)
    {
        $value = is_array($value) ? $value : [$value];
        $this->parameters = [];
        foreach ($value as $name => $binding) {
            is_string($name)
                ? $this->parameters[$name] = $binding
                : $this->parameters[] = $binding;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        $bindings = $this->parameters;

        if ($bindings instanceof Range) {
            $bindings = $bindings->getBindings();
        }

        while ($this->nextClause !== null) {
            foreach ($this->nextClause->getBindings() as $name => $binding) {
                is_string($name)
                    ? $bindings[$name] = $binding
                    : $bindings[] = $binding;
            }
        }

        return $bindings;
    }

    /**
     * @param Formatter $formatter
     * @param string|null $table
     * @return string
     */
    public function toSql(Formatter $formatter, ?string $table): string
    {
        $sql = $this->buildCurrent($formatter, $table);

        // Dig through all chained clauses if exists
        if ($this->nextClause !== null) {
            $nextLogic = $this->nextLogic;
            $nextClause = $this->nextClause;
            while ($nextClause !== null) {
                $sql.= $nextLogic.' '.$nextClause->buildCurrent($formatter, $table);
                $nextLogic = $nextClause->nextLogic;
                $nextClause = $nextClause->nextClause;
            }
            $sql = '('.$sql.')';
        }

        return $sql;
    }

    /**
     * @param Formatter $formatter
     * @param string|null $table
     * @return string
     */
    protected function buildCurrent(Formatter $formatter, ?string $table): string
    {
        // treat it as raw query
        if ($this->column === null) {
            return (string) $this->parameters;
        }

        $column = $formatter->columnName($this->column, $table);
        $operator = $this->operator;
        $negated = $this->negated;
        $value = $this->parameters;

        if ($operator === null) {
            return $column.' '.$value;
        }

        if ($operator === '=') {
            if ($value === null) {
                $expr = $negated ? 'IS NOT NULL' : 'IS NULL';
                return $column.' '.$expr;
            }
            $operator = $negated ? '!'.$operator : $operator;
            return $column.' '.$operator.' '.$formatter->bindName();
        }

        if ($operator === 'IN') {
            if (empty($value)) return '1 = 0';
            $operator = $negated ? 'NOT '.$operator : $operator;
            $bindNames = [];
            for($i = 0, $size = count($value); $i < $size; $i++) {
                $bindNames[] = $formatter->bindName();
            }
            return $column.' '.$operator.' ('.implode(', ', $bindNames).')';
        }

        if ($operator === 'BETWEEN') {
            $operator = $negated ? 'NOT '.$operator : $operator;
            return $column.' '.$operator.' '.$formatter->bindName().' AND '.$formatter->bindName();
        }

        if ($operator === 'LIKE') {
            $operator = $negated ? 'NOT '.$operator : $operator;
            return $column.' '.$operator.' '.$formatter->bindName();
        }

        if ($operator === 'RANGE' && $value instanceof Range) {
            return $value->toSql($formatter, $column, $negated);
        }

        // ">", ">=", "<", "<=" and raw cannot be negated
        if ($negated) {
            // TODO needs better exception
            throw new RuntimeException("Negation not valid for '$operator'");
        }

        if (count($value) > 1) {
            throw new RuntimeException(count($value).' parameters for WHERE '.$operator.' detected where only 1 is expected.');
        }

        return $column.' '.$operator.' '.$formatter->bindName();
    }
}
