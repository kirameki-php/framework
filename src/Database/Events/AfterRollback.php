<?php

namespace Kirameki\Database\Events;

use Kirameki\Event\Event;
use Throwable;

class AfterRollback extends Event
{
    /**
     * @var Throwable
     */
    public Throwable $throwable;

    /**
     * @param Throwable $throwable
     */
    public function __construct(Throwable $throwable)
    {
        $this->throwable = $throwable;
    }
}
