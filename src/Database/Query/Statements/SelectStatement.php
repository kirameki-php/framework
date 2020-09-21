<?php

namespace Kirameki\Database\Query\Statements;

class SelectStatement extends ConditionStatement
{
    /**
     * @var string[]|null
     */
    public ?array $columns;

    /**
     * @var array|null
     */
    public ?array $groupBy;

    /**
     * @var bool
     */
    public ?bool $distinct;

    /**
     * @var bool|null
     */
    public ?bool $lock;
}
