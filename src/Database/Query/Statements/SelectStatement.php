<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Support\Expr;

class SelectStatement extends ConditionsStatement
{
    /**
     * @var array<string|Expr>|null
     */
    public ?array $columns = null;

    /**
     * @var array<string>|null
     */
    public ?array $groupBy = null;

    /**
     * @var ConditionDefinition[]
     */
    public ?array $having = null;

    /**
     * @var int|null
     */
    public ?int $offset = null;

    /**
     * @var bool
     */
    public ?bool $distinct = null;

    /**
     * @var LockType|null
     */
    public LockType|null $lock = null;
}
