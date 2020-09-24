<?php

namespace Kirameki\Database\Query\Statements;

class UpdateStatement extends ConditionalStatement
{
    /**
     * @var array|null
     */
    public ?array $data = null;
}
