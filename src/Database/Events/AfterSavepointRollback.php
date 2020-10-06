<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Transaction\SavepointRollback;
use Kirameki\Event\Event;

class AfterSavepointRollback extends Event
{
    /**
     * @var SavepointRollback
     */
    public SavepointRollback $rollback;

    /**
     * @param SavepointRollback $rollback
     */
    public function __construct(SavepointRollback $rollback)
    {
        $this->rollback = $rollback;
    }
}
