<?php

namespace Kirameki\Database\Schema\Builders;

class ColumnBuilderAggregate
{
    /**
     * @var ColumnBuilder[]
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
    public function notNull(): static
    {
        foreach ($this->columns as $column) {
            $column->notNull();
        }
        return $this;
    }
}
