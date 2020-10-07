<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @var string
     */
    public string $statement;

    /**
     * @var array
     */
    public array $bindings;

    /**
     * @var float
     */
    public float $time;

    /**
     * @param Connection $connection
     * @param string $statement
     * @param array $bindings
     * @param float $time
     */
    public function __construct(Connection $connection, string $statement, array $bindings, float $time)
    {
        parent::__construct($connection);
        $this->statement = $statement;
        $this->bindings = $bindings;
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $formatter = $this->connection->getQueryFormatter();
        return $formatter->interpolate($this->statement, $this->bindings);
    }
}
