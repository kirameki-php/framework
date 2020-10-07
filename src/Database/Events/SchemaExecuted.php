<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;

class SchemaExecuted extends DatabaseEvent
{
    /**
     * @var string
     */
    public string $statement;

    /**
     * @var float
     */
    public float $time;

    /**
     * @param Connection $connection
     * @param string $statement
     * @param float $time
     */
    public function __construct(Connection $connection, string $statement, float $time)
    {
        parent::__construct($connection);
        $this->statement = $statement;
        $this->time = $time;
    }
}
