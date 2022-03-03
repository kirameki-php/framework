<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Support\Expr;
use Kirameki\Support\Concerns\Tappable;

class ConditionDefinition
{
    use Tappable;

    /**
     * @var string|null
     */
    public ?string $column;

    /**
     * @var Operator|null
     */
    public ?Operator $operator;

    /**
     * @var bool
     */
    public bool $negated;

    /**
     * @var Expr|SelectBuilder|iterable<mixed>|null
     */
    public Expr|SelectBuilder|iterable|null $value;

    /**
     * @var string|null
     */
    public ?string $nextLogic;

    /**
     * @var static|null
     */
    public ?self $next;

    /**
     * @param string|null $column
     */
    public function __construct(string $column = null)
    {
        $this->column = $column;
        $this->negated = false;
        $this->operator = null;
        $this->value = null;
        $this->nextLogic = null;
        $this->next = null;
    }

    public function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
        }
    }
}
