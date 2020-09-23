<?php

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\WhereClause;

class ConditionStatement extends BaseStatement
{
    /**
     * @var WhereClause[]
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
        foreach ($this->where as $clause) {
            $where[] = clone $clause;
        }
        $this->where = $where;
    }
}
