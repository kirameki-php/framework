<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @var string
     */
    public string $query;

    /**
     * @var array
     */
    public array $bindings;

    /**
     * @var float|null
     */
    public ?float $time;

    /**
     * @param Connection $connection
     * @param string $query
     * @param array $bindings
     */
    public function __construct(Connection $connection, string $query, array $bindings = [], ?float $time = null)
    {
        parent::__construct($connection);
        $this->query = $query;
        $this->bindings = $bindings;
        $this->time = $time;
    }
}
