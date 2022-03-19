<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Expressions\Table;

abstract class BaseStatement
{
    /**
     * @var Table|null
     */
    public ?Table $table = null;
}
