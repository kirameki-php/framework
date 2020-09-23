<?php

namespace Kirameki\Database\Query\Statements;

class SelectStatement extends ConditionStatement
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
     * @var bool
     */
    public ?bool $distinct = null;

    /**
     * @var bool|null
     */
    public ?bool $lock = null;
}
