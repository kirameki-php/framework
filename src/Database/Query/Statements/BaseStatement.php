<?php

namespace Kirameki\Database\Query\Statements;

abstract class BaseStatement
{
    /**
     * @var string|null
     */
    public ?string $table = null;

    /**
     * @var string|null
     */
    public ?string $tableAlias = null;
}
