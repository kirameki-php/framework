<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Support\Concerns\Tappable;
use RuntimeException;
use Traversable;

class ConditionBuilder
{
    use Tappable;

    /**
     * @var ConditionDefinition
     */
    protected ConditionDefinition $root;

    /**
     * @var ConditionDefinition
     */
    protected ConditionDefinition $current;

    /**
     * @var bool
     */
    protected bool $defined;

    /**
     * @param string $column
     * @return static
     */
    public static function for(string $column): static
    {
        return new static($column);
    }

    /**
     * @param string $raw
     * @return static
     */
    public static function raw(string $raw): static
    {
        $instance = new static();
        $instance->parameter($raw);
        return $instance;
    }

    /**
     * @param string|null $column
     */
    protected function __construct(?string $column = null)
    {
        $this->root = $this->current = new ConditionDefinition($column);
        $this->defined = false;
    }

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        $this->root = clone $this->root;

        // $this->current should always point to the last condition
        $this->current = $this->root;
        while($this->current->next !== null) {
            $this->current = $this->current->next;
        }
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function and(?string $column = null): static
    {
        $nextDefinition = new ConditionDefinition($column);
        $this->current->nextLogic = 'AND';
        $this->current->next = $nextDefinition;
        $this->current = $nextDefinition;
        $this->defined = false;
        return $this;
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function or(?string $column = null): static
    {
        $nextDefinition = new ConditionDefinition($column ?? $this->current->column);
        $this->current->nextLogic = 'OR';
        $this->current->next = $nextDefinition;
        $this->current = $nextDefinition;
        $this->defined = false;
        return $this;
    }

    /**
     * @return $this
     */
    protected function negate(): static
    {
        $this->current->negated = true;
        return $this;
    }

    /**
     * @param string $operator
     * @param $value
     * @return $this
     */
    public function with(string $operator, $value): static
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
     * @see ConditionBuilder::equals()
     * @param $value
     * @return $this
     */
    public function eq($value): static
    {
        return $this->equals($value);
    }

    /**
     * @see ConditionBuilder::notEquals()
     * @param $value
     * @return $this
     */
    public function ne($value): static
    {
        return $this->notEquals($value);
    }

    /**
     * @see ConditionBuilder::lessThan()
     * @param $value
     * @return $this
     */
    public function lt($value): static
    {
        return $this->lessThan($value);
    }

    /**
     * @see ConditionBuilder::lessThanOrEquals()
     * @param $value
     * @return $this
     */
    public function lte($value): static
    {
        return $this->lessThanOrEquals($value);
    }

    /**
     * @see ConditionBuilder::greaterThan()
     * @param $value
     * @return $this
     */
    public function gt($value): static
    {
        return $this->greaterThan($value);
    }

    /**
     * @see ConditionBuilder::greaterThanOrEquals()
     * @param $value
     * @return $this
     */
    public function gte($value): static
    {
        return $this->greaterThanOrEquals($value);
    }


    /**
     * @param $value
     * @return $this
     */
    public function equals($value): static
    {
        $this->current->negated = false;
        $this->current->operator = '=';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function notEquals($value): static
    {
        return $this->equals($value)->negate();
    }

    /**
     * @return $this
     */
    public function greaterThanOrEquals($value): static
    {
        $this->current->negated = false;
        $this->current->operator = '>=';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @return $this
     */
    public function greaterThan($value): static
    {
        $this->current->negated = false;
        $this->current->operator = '>';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function lessThanOrEquals($value): static
    {
        $this->current->negated = false;
        $this->current->operator = '<=';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function lessThan($value): static
    {
        $this->current->negated = false;
        $this->current->operator = '<';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function like(string $value): static
    {
        $this->current->negated = false;
        $this->current->operator = 'LIKE';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function notLike(string $value): static
    {
        return $this->like($value)->negate();
    }

    /**
     * @param StatementBuilder|iterable $value
     * @return $this
     */
    public function in($value): static
    {
        $this->current->negated = false;
        $this->current->operator = 'IN';
        if (is_iterable($value)) {
            $value = ($value instanceof Traversable) ? iterator_to_array($value) : (array)$value;
            $value = array_filter($value, static fn($s) => $s !== null);
            $value = array_unique($value);
        }
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param $values
     * @return $this
     */
    public function notIn($values): static
    {
        return $this->in($values)->negate();
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     */
    public function between($min, $max): static
    {
        $this->current->negated = false;
        $this->current->operator = 'BETWEEN';
        $this->current->parameters = [$min, $max];
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     */
    public function notBetween($min, $max): static
    {
        return $this->between($min, $max)->negate();
    }

    /**
     * @param Range $range
     * @return $this
     */
    public function inRange(Range $range): static
    {
        $this->current->negated = false;
        $this->current->operator = 'RANGE';
        $this->current->parameters = $range;
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param Range $range
     * @return $this
     */
    public function notInRange(Range $range): static
    {
        return $this->inRange($range)->negate();
    }

    /**
     * @param array|mixed $value
     * @return $this
     */
    public function parameter($value): static
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
     * @return ConditionDefinition
     */
    public function getDefinition(): ConditionDefinition
    {
        return $this->root;
    }

    /**
     * @return $this
     */
    protected function markAsDefined(): static
    {
        if ($this->defined) {
            throw new RuntimeException('Tried to set condition when it was already set!');
        }
        $this->defined = true;
        return $this;
    }
}
