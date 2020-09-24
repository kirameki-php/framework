<?php

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Condition;

class SelectStatement extends ConditionalStatement
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
     * @var Condition[]
     */
    public ?array $having = null;

    /**
     * @var bool
     */
    public ?bool $distinct = null;

    /**
     * @var bool|null
     */
    public ?bool $lock = null;
}
