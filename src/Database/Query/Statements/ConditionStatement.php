<?php

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\WhereClause;

class ConditionStatement extends BaseStatement
{
    /**
     * @var WhereClause[]
     */
    public ?array $where;

    /**
     * @var array|null
     */
    public ?array $orderBy;

    /**
     * @var int|null
     */
    public ?int $limit;

    /**
     * @var int|null
     */
    public ?int $offset;

    public function __clone()
    {
        $where = [];
        foreach ($this->where as $clause) {
            $where[] = clone $clause;
        }
        $this->where = $where;
    }
}
