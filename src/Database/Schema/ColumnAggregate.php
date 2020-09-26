<?php

namespace Kirameki\Database\Schema;

/**
 * @mixin Column
 */
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
     * @param string $method
     * @param $args
     * @return $this
     */
    public function __call(string $method, array $args)
    {
        foreach ($this->columns as $column) {
            $column->$method(...$args);
        }
        return $this;
    }
}
