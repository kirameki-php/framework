<?php

namespace Kirameki\Database\Schema\Builders;

class ColumnAggregate
{
    /**
     * @var Column[]
     */
    public array $columns;

    /**
     * @param array $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return $this
     */
    public function notNull()
    {
        foreach ($this->columns as $column) {
            $column->notNull();
        }
        return $this;
    }
}
