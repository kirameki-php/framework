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
     * @var bool
     */
    protected bool $propagate;

    /**
     * @param Closure $listener
     * @param bool $once
     */
    public function __construct(Closure $listener, bool $once = false)
    {
        $this->listener = $listener;
        $this->once = $once;
        $this->listening = true;
        $this->propagate = true;
    }

    /**
     * @param Event $event
     */
    public function invoke(Event $event): void
    {
        if ($this->listening) {
            if ($this->once) {
                $this->stopListening();
            }
            call_user_func($this->listener, $event, $this);
        }
    }

    /**
     * @return void
     */
    public function stopListening(): void
    {
        $this->listening = false;
    }

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
    public function isListening(): bool
    {
        return $this->listening;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return !$this->propagate;
    }
}
