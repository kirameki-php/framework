<?php

namespace Kirameki\Database\Query\Statements;

class UpdateStatement extends ConditionStatement
{
    /**
     * @var array|null
     */
    public ?array $data;
}
