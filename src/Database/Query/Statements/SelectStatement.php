<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class SelectStatement extends ConditionsStatement
{
    /**
     * @var string[]|null
     */
    public ?array $columns = null;

    /**
     * @var array|null
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
     * @var bool|null
     */
    public ?bool $lock = null;
}
