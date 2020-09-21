<?php

namespace Kirameki\Database\Query\Statements;

abstract class BaseStatement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @var string|null
     */
    public ?string $tableAlias;
}
