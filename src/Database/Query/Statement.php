<?php

namespace Kirameki\Database\Query;

class Statement
{
    /**
     * @var Formatter
     */
    protected Formatter $formatter;

    /**
     * @var string
     */
    public string $from;

    /**
     * @var string|null
     */
    public ?string $as;

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

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function __clone()
    {
        $where = [];
        foreach ($this->where as $clause) {
            $where[] = clone $clause;
        }
        $this->where = $where;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->formatter->statement($this);
    }
}
