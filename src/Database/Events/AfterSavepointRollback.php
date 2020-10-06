<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\SavepointRollback;

class AfterSavepointRollback extends DatabaseEvent
{
    /**
     * @var SavepointRollback
     */
    public SavepointRollback $rollback;

    /**
     * @param Connection $connection
     * @param SavepointRollback $rollback
     */
    public function __construct(Connection $connection, SavepointRollback $rollback)
    {
        parent::__construct($connection);
        $this->rollback = $rollback;
    }
}
