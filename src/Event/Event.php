<?php

namespace Kirameki\Event;

class Event
{
    /**
     * @var bool
     */
    protected bool $propagate = true;

    /**
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagate = false;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return !$this->propagate;
    }
}
