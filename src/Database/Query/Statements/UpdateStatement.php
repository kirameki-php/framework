<?php

namespace Kirameki\Database\Query\Statements;

class UpdateStatement extends ConditionsStatement
{
    /**
     * @var array|null
     */
    public ?array $data = null;
}
