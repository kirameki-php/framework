<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Transaction\Savepoint;
use Kirameki\Event\Event;

class AfterSavepoint extends Event
{
    /**
     * @var Savepoint
     */
    public Savepoint $savepoint;

    /**
     * @param Savepoint $savepoint
     */
    public function __construct(Savepoint $savepoint)
    {
        $this->savepoint = $savepoint;
    }
}
