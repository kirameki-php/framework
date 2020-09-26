<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Builders\Builder;
use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Support\Concerns\Tappable;
use RuntimeException;
use Traversable;
use function PHPUnit\Framework\greaterThan;
use function PHPUnit\Framework\greaterThanOrEqual;
use function PHPUnit\Framework\lessThan;

class Condition
{
    use Tappable;

    protected ?string $column;

    protected ?string $operator;

    protected bool $negated;

    protected $parameters;

    protected bool $defined;

    protected ?string $nextLogic;

    protected ?self $next;

    protected self $current;

    /**
     * @param string $column
     * @return static
     */
    public static function for(string $column)
    {
        return new static($column);
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
    protected function __construct(string $column = null)
    {
        $this->column = $column;
        $this->negated = false;
        $this->operator = null;
        $this->parameters = null;
        $this->defined = false;
        $this->nextLogic = null;
        $this->next = null;
        $this->current = $this;
    }

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
        }

        // $this->current should always point to the last condition
        $this->current = $this;
        while($this->current->next !== null) {
            $this->current = $this->current->next;
        }
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function and(?string $column = null)
    {
        $nextCond = static::for($column ?? $this->column);
        $this->current->nextLogic = 'AND';
        $this->current->next = $nextCond;
        $this->current = $nextCond;
        return $this;
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function or(?string $column = null)
    {
        $nextCond = static::for($column ?? $this->column);
        $this->current->nextLogic = 'OR';
        $this->current->next = $nextCond;
        $this->current = $nextCond;
        return $this;
    }

    /**
     * @return $this
     */
    protected function negate()
    {
        $this->current->negated = true;
        return $this;
    }

    /**
     * @param string $operator
     * @param $value
     * @return $this
     */
    public function with(string $operator, $value)
    {
        if ($operator === '=') return $this->equals($value);
        if ($operator === '!=') return $this->notEquals($value);
        if ($operator === '<>') return $this->notEquals($value);
        if ($operator === '>') return $this->greaterThan($value);
        if ($operator === '>=') return $this->greaterThanOrEquals($value);
        if ($operator === '<') return $this->lessThan($value);
        if ($operator === '<=') return $this->lessThanOrEquals($value);
        if ($operator === 'IN') return $this->in($value);
        if ($operator === 'NOT IN') return $this->notIn($value);
        if ($operator === 'BETWEEN') return $this->between($value[0], $value[1]);
        if ($operator === 'NOT BETWEEN') return $this->notBetween($value[0], $value[1]);
        if ($operator === 'LIKE') return $this->like($value);
        if ($operator === 'NOT LIKE') return $this->notLike($value);
        throw new RuntimeException('Unknown operator:' . $operator);
    }

    /**
     * @see equals()
     * @param $value
     * @return $this
     */
    public function eq($value)
    {
        return $this->equals($value);
    }

    /**
     * @see notEquals()
     * @param $value
     * @return $this
     */
    public function ne($value)
    {
        return $this->notEquals($value);
    }

    /**
     * @see lessThan()
     * @param $value
     * @return $this
     */
    public function lt($value)
    {
        return $this->lessThan($value);
    }

    /**
     * @see lessThanOrEquals()
     * @param $value
     * @return $this
     */
    public function lte($value)
    {
        return $this->lessThanOrEquals($value);
    }

    /**
     * @see greaterThan()
     * @param $value
     * @return $this
     */
    public function gt($value)
    {
        return $this->greaterThan($value);
    }

    /**
     * @see greaterThanOrEquals()
     * @param $value
     * @return $this
     */
    public function gte($value)
    {
        return $this->greaterThanOrEquals($value);
    }


    /**
     * @param $value
     * @return $this
     */
    public function equals($value)
    {
        $this->current->negated = false;
        $this->current->operator = '=';
        $this->parameter($value);
        $this->current->markAsDefined();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function notEquals($value)
    {
        return $this->equals($value)->negate();
    }

    /**
     * @return $this
     */
    public function greaterThanOrEquals($value)
    {
        $this->current->negated = false;
        $this->current->operator = '>=';
        $this->parameter($value);
        $this->current->markAsDefined();
        return $this;
    }

    /**
     * @return $this
     */
    public function greaterThan($value)
    {
        $this->current->negated = false;
        $this->current->operator = '>';
        $this->parameter($value);
        $this->current->markAsDefined();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function lessThanOrEquals($value)
    {
        $this->current->negated = false;
        $this->current->operator = '<=';
        $this->parameter($value);
        $this->current->markAsDefined();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function lessThan($value)
    {
        $this->current->negated = false;
        $this->current->operator = '<';
        $this->parameter($value);
        $this->current->markAsDefined();
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function like(string $value)
    {
        $this->current->negated = false;
        $this->current->operator = 'LIKE';
        $this->parameter($value);
        $this->current->markAsDefined();
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
        $this->current->negated = false;
        $this->current->operator = 'IN';
        if (is_iterable($value)) {
            $value = ($value instanceof Traversable) ? iterator_to_array($value) : (array)$value;
            $value = array_filter($value, static fn($s) => $s !== null);
            $value = array_unique($value);
        }
        $this->parameter($value);
        $this->current->markAsDefined();
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
        $this->current->negated = false;
        $this->current->operator = 'BETWEEN';
        $this->current->parameters = [$min, $max];
        $this->current->markAsDefined();
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
        $this->current->negated = false;
        $this->current->operator = 'RANGE';
        $this->current->parameters = $range;
        $this->current->markAsDefined();
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
        $this->current->parameters = [];
        foreach ($value as $name => $binding) {
            is_string($name)
                ? $this->current->parameters[$name] = $binding
                : $this->current->parameters[] = $binding;
        }
        return $this;
    }

    /**
     * @param Formatter $formatter
     * @param string|null $table
     * @return string
     */
    public function toSql(Formatter $formatter, ?string $table): string
    {
        $parts = [];
        $parts[] = $this->buildCurrent($formatter, $table);

        // Dig through all chained clauses if exists
        if ($this->next !== null) {
            $logic = $this->nextLogic;
            $cond = $this->next;
            while ($cond !== null) {
                $parts[]= $logic.' '.$cond->buildCurrent($formatter, $table);
                $logic = $cond->nextLogic;
                $cond = $cond->next;
            }
        }

        return (count($parts) > 1) ? '('.implode(' ', $parts).')': $parts[0];
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
            throw new RuntimeException(count($value).' parameters for condition detected where only 1 is expected.');
        }

        return $column.' '.$operator.' '.$formatter->bindName();
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

        $cond = $this->next;
        while ($cond !== null) {
            foreach ($cond->getBindings() as $name => $binding) {
                is_string($name)
                    ? $bindings[$name] = $binding
                    : $bindings[] = $binding;
            }
            $cond = $cond->next;
        }

        return $bindings;
    }

    /**
     * @return $this
     */
    protected function markAsDefined()
    {
        if ($this->defined) {
            throw new RuntimeException('Tried to set condition when it was already set!');
        }
        $this->defined = true;
        return $this;
    }
}
