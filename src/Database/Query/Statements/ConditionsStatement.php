<?php

namespace Kirameki\Database\Query\Statements;

abstract class ConditionsStatement extends BaseStatement
{
    /**
     * @var ConditionDefinition[]
     */
    public ?array $where = null;

    /**
     * @var array|null
     */
    public ?array $orderBy = null;

    /**
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * @var int|null
     */
    public ?int $offset = null;

    public function __clone()
    {
        $where = [];
        foreach ($this->where as $condition) {
            $where[] = clone $condition;
        }
        $this->where = $where;
    }
}
