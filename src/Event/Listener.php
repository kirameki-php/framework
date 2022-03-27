<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;

class Listener
{
    /**
     * @var Closure
     */
    protected Closure $callback;

    /**
     * @var bool
     */
    protected bool $once;

    /**
     * @var bool
     */
    protected bool $listening;

    /**
     * @param Closure $callback
     * @param bool $once
     */
    public function __construct(Closure $callback, bool $once = false)
    {
        $this->callback = $callback;
        $this->once = $once;
        $this->listening = true;
    }

    /**
     * @param Event $event
     * @return void
     */
    public function invoke(Event $event): void
    {
        if ($this->listening) {
            if ($this->once) {
                $this->stopListening();
            }
            ($this->callback)($event, $this);
        }
    }

    /**
     * @return Closure
     */
    public function getCallback(): Closure
    {
        return $this->callback;
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
