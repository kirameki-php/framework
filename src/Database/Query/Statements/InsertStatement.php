<?php

namespace Kirameki\Database\Query\Statements;

class InsertStatement extends BaseStatement
{
    /**
     * @var array[]|null
     */
    public ?array $dataset = null;
}
