<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class DeleteStatement extends ConditionsStatement
{
    /**
     * @var array<string>|null
     */
    public ?array $returningColumns = null;
}
