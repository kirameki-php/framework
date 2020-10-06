<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Throwable;

class RollbackExecuted extends DatabaseEvent
{
    /**
     * @var Throwable
     */
    public Throwable $throwable;

    /**
     * @param Connection $connection
     * @param Throwable $throwable
     */
    public function __construct(Connection $connection, Throwable $throwable)
    {
        parent::__construct($connection);
        $this->throwable = $throwable;
    }
}
