<?php

namespace Kirameki\Database\Query;

class Statement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @var string|null
     */
    public ?string $tableAlias;

    /**
     * @var bool
     */
    public bool $distinct = false;

    /**
     * @var array|null
     */
    public ?array $select;

    /**
     * @var WhereClause[]
     */
    public ?array $where;

    /**
     * @var array|null
     */
    public ?array $groupBy;

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

    /**
     * @var bool|null
     */
    public ?bool $lock;

    public function __clone()
    {
        $where = [];
        foreach ($this->where as $clause) {
            $where[] = clone $clause;
        }
        $this->where = $where;
    }
}
