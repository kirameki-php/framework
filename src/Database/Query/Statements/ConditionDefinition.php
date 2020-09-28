<?php

namespace Kirameki\Database\Query\Statements;

use Kirameki\Support\Concerns\Tappable;

class ConditionDefinition
{
    use Tappable;

    public ?string $column;

    public ?string $operator;

    public bool $negated;

    public $parameters;

    public ?string $nextLogic;

    public ?self $next;

    /**
     * @param string|null $column
     */
    public function __construct(string $column = null)
    {
        $this->column = $column;
        $this->negated = false;
        $this->operator = null;
        $this->parameters = null;
        $this->nextLogic = null;
        $this->next = null;
    }

    public function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
        }
    }
}
