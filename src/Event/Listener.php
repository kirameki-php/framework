<?php

namespace Kirameki\Event;

use Closure;

class Listener
{
    /**
     * @var Closure
     */
    protected Closure $listener;

    /**
     * @var bool
     */
    protected bool $once;

    /**
     * @var bool
     */
    protected bool $listening;

    /**
     * @param Closure $listener
     * @param bool $once
     */
    public function __construct(Closure $listener, bool $once = false)
    {
        $this->listener = $listener;
        $this->once = $once;
        $this->listening = true;
    }

    /**
     * @param Event $event
     * @return mixed
     */
    public function invoke(Event $event)
    {
        if ($this->listening) {
            if ($this->once) {
                $this->stopListening();
            }

            $listener = $this->listener;
            return $listener($event, $this);
        }
        return null;
    }

    /**
     * @return void
     */
    public function stopListening(): void
    {
        $this->listening = false;
    }

    /**
     * @return bool
     */
    public function isListening(): bool
    {
        return $this->listening;
    }
}
