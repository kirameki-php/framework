<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Expressions\Expr;
use Kirameki\Database\Query\Expressions\Raw;
use Kirameki\Support\Concerns\Tappable;
use RuntimeException;
use Traversable;
use function is_iterable;

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
        return (new static())->expr(new Raw($raw));
    }

    /**
     * @param SelectBuilder $builder
     * @return static
     */
    public static function nest(SelectBuilder $builder): static
    {
        return (new static())->nested($builder);
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
        $this->current->nextLogic = 'AND';
        $this->current->next = new ConditionDefinition($column ?? $this->current->column);
        return $this->setCurrent($this->current->next);
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function or(?string $column = null): static
    {
        $this->current->nextLogic = 'OR';
        $this->current->next = new ConditionDefinition($column ?? $this->current->column);
        return $this->setCurrent($this->current->next);
    }

    /**
     * @param ConditionDefinition $next
     * @return $this
     */
    protected function setCurrent(ConditionDefinition $next): static
    {
        $this->current = $next;
        $this->defined = false;
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     * @see equals()
     */
    public function eq(mixed $value): static
    {
        return $this->equals($value);
    }

    /**
     * @param mixed $value
     * @return $this
     * @see notEquals()
     */
    public function ne(mixed $value): static
    {
        return $this->notEquals($value);
    }

    /**
     * @param mixed $value
     * @return $this
     * @see lessThan()
     */
    public function lt(mixed $value): static
    {
        return $this->lessThan($value);
    }

    /**
     * @param mixed $value
     * @return $this
     *@see lessThanOrEqualTo()
     */
    public function lte(mixed $value): static
    {
        return $this->lessThanOrEqualTo($value);
    }

    /**
     * @param mixed $value
     * @return $this
     *@see greaterThan()
     */
    public function gt(mixed $value): static
    {
        return $this->greaterThan($value);
    }

    /**
     * @param mixed $value
     * @return $this
     *@see greaterThanOrEqualTo()
     */
    public function gte(mixed $value): static
    {
        return $this->greaterThanOrEqualTo($value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function equals(mixed $value): static
    {
        if (is_iterable($value)) {
            throw new RuntimeException('Iterable should use in(iterable $iterable) method instead');
        }
        // value will not be set for binding if null since it will be converted to IS [NOT] NULL
        $value = $value !== null ? [$value] : null;
        return $this->define(Operator::Equals, $value);
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
    public function greaterThanOrEqualTo(mixed $value): static
    {
        return $this->define(Operator::GreaterThanOrEqualTo, [$value]);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThan(mixed $value): static
    {
        return $this->define(Operator::GreaterThan, [$value]);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThanOrEqualTo(mixed $value): static
    {
        return $this->define(Operator::LessThanOrEqualTo, [$value]);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThan(mixed $value): static
    {
        return $this->define(Operator::LessThan, [$value]);
    }

    /**
     * @return $this
     */
    public function isNull(): static
    {
        return $this->equals(null);
    }

    /**
     * @return $this
     */
    public function isNotNull(): static
    {
        return $this->isNull()->negate();
    }

    /**
     * @param string $value
     * @return $this
     */
    public function like(string $value): static
    {
        return $this->define(Operator::Like, [$value]);
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
     * @param iterable<mixed>|SelectBuilder $values
     * @return $this
     */
    public function in(iterable|SelectBuilder $values): static
    {
        if (is_iterable($values)) {
            $values = ($values instanceof Traversable) ? iterator_to_array($values) : (array) $values;
            $values = array_filter($values, static fn($s) => $s !== null);
            $values = array_unique($values);
        }
        return $this->define(Operator::In, $values);
    }

    /**
     * @param iterable<int, mixed>|SelectBuilder $values
     * @return $this
     */
    public function notIn(iterable|SelectBuilder $values): static
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
        return $this->define(Operator::Between, [$min, $max]);
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
        return $this->define(Operator::Range, $range);
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
     * @param Raw $raw
     * @return $this
     */
    public function expr(Raw $raw): static
    {
        return $this->define(Operator::Raw, $raw);
    }

    /**
     * @param SelectBuilder $builder
     * @return $this
     */
    public function exists(SelectBuilder $builder): static
    {
        return $this->define(Operator::Exists, $builder);
    }

    /**
     * @param SelectBuilder $builder
     * @return $this
     */
    public function nested(SelectBuilder $builder): static
    {
        return $this->define(Operator::Nested, $builder);
    }

    /**
     * @param static $builder
     * @return $this
     */
    public function apply(self $builder): static
    {
        $this->current->column = $builder->current->column;
        $this->current->operator = $builder->current->operator;
        $this->current->value = $builder->current->value;
        $this->current->negated = $builder->current->negated;
        $this->defined = true;
        return $this;
    }

    /**
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function match(string $operator, mixed $value): static
    {
        return match (strtoupper(trim($operator))) {
            '=' => $this->equals($value),
            '!=', '<>' => $this->notEquals($value),
            '>' => $this->greaterThan($value),
            '>=' => $this->greaterThanOrEqualTo($value),
            '<' => $this->lessThan($value),
            '<=' => $this->lessThanOrEqualTo($value),
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
     * @return $this
     */
    public function negate(): static
    {
        $this->current->negated = !$this->current->negated;
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
     * @param Operator $operator
     * @param Expr|SelectBuilder|iterable<mixed>|null $value
     * @return $this
     */
    protected function define(Operator $operator, Expr|SelectBuilder|iterable|null $value): static
    {
        $this->current->operator = $operator;
        $this->current->value = $value;
        if ($this->defined) {
            throw new RuntimeException('Tried to set condition when it was already set!');
        }
        $this->defined = true;
        return $this;
    }
}
