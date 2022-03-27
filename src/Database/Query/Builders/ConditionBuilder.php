<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Closure;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Expressions\Expr;
use Kirameki\Database\Query\Expressions\Raw;
use Kirameki\Support\Concerns\Tappable;
use RuntimeException;
use Traversable;
use function count;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function strtoupper;

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
     * @param mixed ...$args
     * @return static
     */
    public static function fromArgs(mixed ...$args): static
    {
        $num = count($args);

        if (($num === 1)) {
            $condition = $args[0];
            if ($condition instanceof static) {
                return $condition;
            }
        }

        if ($num === 2) {
            [$column, $value] = $args;

            if (is_string($column)) {
                if ($value instanceof Range) {
                    return self::for($column)->inRange($value);
                }

                if (is_iterable($value)) {
                    return self::for($column)->in($value);
                }

                return self::for($column)->equals($value);
            }
        }

        if ($num === 3) {
            [$column, $operator, $value] = $args;
            if (is_string($column) && is_string($operator)) {
                return self::for($column)->match($operator, $value);
            }
        }

        throw new RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

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
     * @see equals()
     */
    public function eq(mixed $value): static
    {
        return $this->equals($value);
    }

    /**
     * @see notEquals()
     */
    public function ne(mixed $value): static
    {
        return $this->notEquals($value);
    }

    /**
     * @see lessThan()
     */
    public function lt(mixed $value): static
    {
        return $this->lessThan($value);
    }

    /**
     * @see lessThanOrEqualTo()
     */
    public function lte(mixed $value): static
    {
        return $this->lessThanOrEqualTo($value);
    }

    /**
     * @see greaterThan()
     */
    public function gt(mixed $value): static
    {
        return $this->greaterThan($value);
    }

    /**
     * @see greaterThanOrEqualTo()
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
        $value = $this->toValue($value);

        if (is_iterable($value)) {
            throw new RuntimeException('Iterable should use in(iterable $iterable) method instead');
        }

        // value will not be set for binding if null since it will be converted to IS [NOT] NULL
        if ($value !== null) {
            $value =  [$value];
        }

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
        return $this->define(Operator::GreaterThanOrEqualTo, [$this->toValue($value)]);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThan(mixed $value): static
    {
        return $this->define(Operator::GreaterThan, [$this->toValue($value)]);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThanOrEqualTo(mixed $value): static
    {
        return $this->define(Operator::LessThanOrEqualTo, [$this->toValue($value)]);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThan(mixed $value): static
    {
        return $this->define(Operator::LessThan, [$this->toValue($value)]);
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
        return $this->define(Operator::Like, [$this->toValue($value)]);
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
        $_values = $this->toValue($values);

        if (is_iterable($_values)) {
            $_values = ($_values instanceof Traversable) ? iterator_to_array($_values) : (array) $_values;
            $_values = array_filter($_values, static fn($s) => $s !== null);
            $_values = array_unique($_values);
        }

        return $this->define(Operator::In, $_values);
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
     * @param Expr $expr
     * @return $this
     */
    public function expr(Expr $expr): static
    {
        return $this->define(Operator::Raw, $expr);
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
        return match (strtoupper($operator)) {
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
     * @param Expr|Range|SelectBuilder|array<mixed>|null $value
     * @return $this
     */
    protected function define(Operator $operator, Expr|Range|SelectBuilder|array|null $value): static
    {
        $this->current->operator = $operator;
        $this->current->value = $value;
        if ($this->defined) {
            throw new RuntimeException('Tried to set condition when it was already set!');
        }
        $this->defined = true;
        return $this;
    }

    /**
     * @param mixed $var
     * @return Expr|Range|SelectBuilder|array<mixed>|null
     */
    protected function toValue(mixed $var): mixed
    {
        return ($var instanceof Closure) ? $var() : $var;
    }
}
