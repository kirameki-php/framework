<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Event\Event;

class DatabaseEvent extends Event
{
    /**
     * @var Connection
     */
    public Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}
