<?php declare(strict_types=1);

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
     * @param mixed $value
     * @return $this
     */
    public function with(string $operator, mixed $value): static
    {
        return match (strtoupper($operator)) {
            '=' => $this->equals($value),
            '!=', '<>' => $this->notEquals($value),
            '>' => $this->greaterThan($value),
            '>=' => $this->greaterThanOrEquals($value),
            '<' => $this->lessThan($value),
            '<=' => $this->lessThanOrEquals($value),
            'IN' => $this->in($value),
            'NOT IN' => $this->notIn($value),
            'BETWEEN' => $this->between($value[0], $value[1]),
            'NOT BETWEEN' => $this->notBetween($value[0], $value[1]),
            'LIKE' => $this->like($value),
            'NOT LIKE' => $this->notLike($value),
            default => throw new RuntimeException('Unknown operator:' . $operator),
        };
    }

    /**
     * @see ConditionBuilder::equals()
     * @param mixed $value
     * @return $this
     */
    public function eq(mixed $value): static
    {
        return $this->equals($value);
    }

    /**
     * @see ConditionBuilder::notEquals()
     * @param mixed $value
     * @return $this
     */
    public function ne(mixed $value): static
    {
        return $this->notEquals($value);
    }

    /**
     * @see ConditionBuilder::lessThan()
     * @param mixed $value
     * @return $this
     */
    public function lt(mixed $value): static
    {
        return $this->lessThan($value);
    }

    /**
     * @see ConditionBuilder::lessThanOrEquals()
     * @param mixed $value
     * @return $this
     */
    public function lte(mixed $value): static
    {
        return $this->lessThanOrEquals($value);
    }

    /**
     * @see ConditionBuilder::greaterThan()
     * @param mixed $value
     * @return $this
     */
    public function gt(mixed $value): static
    {
        return $this->greaterThan($value);
    }

    /**
     * @see ConditionBuilder::greaterThanOrEquals()
     * @param mixed $value
     * @return $this
     */
    public function gte(mixed $value): static
    {
        return $this->greaterThanOrEquals($value);
    }


    /**
     * @param mixed $value
     * @return $this
     */
    public function equals(mixed $value): static
    {
        $this->current->negated = false;
        $this->current->operator = '=';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function notEquals(mixed $value): static
    {
        return $this->equals($value)->negate();
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThanOrEquals(mixed $value): static
    {
        $this->current->negated = false;
        $this->current->operator = '>=';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThan(mixed $value): static
    {
        $this->current->negated = false;
        $this->current->operator = '>';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThanOrEquals(mixed $value): static
    {
        $this->current->negated = false;
        $this->current->operator = '<=';
        $this->parameter($value);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThan(mixed $value): static
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
     * @param iterable<mixed>|StatementBuilder $values
     * @return $this
     */
    public function in(iterable|StatementBuilder $values): static
    {
        $this->current->negated = false;
        $this->current->operator = 'IN';
        if (is_iterable($values)) {
            $values = ($values instanceof Traversable) ? iterator_to_array($values) : (array)$values;
            $values = array_filter($values, static fn($s) => $s !== null);
            $values = array_unique($values);
        }
        $this->parameter($values);
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param iterable<int, mixed>|StatementBuilder $values
     * @return $this
     */
    public function notIn(iterable|StatementBuilder $values): static
    {
        return $this->in($values)->negate();
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @return $this
     */
    public function between(mixed $min, mixed $max): static
    {
        $this->current->negated = false;
        $this->current->operator = 'BETWEEN';
        $this->current->parameters = [$min, $max];
        $this->markAsDefined();
        return $this;
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @return $this
     */
    public function notBetween(mixed $min, mixed $max): static
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
     * @param mixed $value
     * @return $this
     */
    public function parameter(mixed $value): static
    {
        $value = is_iterable($value) ? $value : [$value];
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
