<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class UpdateStatement extends ConditionsStatement
{
    /**
     * @var array|null
     */
    public ?array $data = null;
}
