<?php

namespace Kirameki\Event;

use Closure;

class EventManager
{
    /**
     * @var Listener[][]
     */
    protected array $events;

    /**
     */
    public function __construct()
    {
        $this->events = [];
    }

    /**
     * @param string $name
     * @param Closure $listener
     */
    public function listen(string $name, Closure $listener): void
    {
        $this->events[$name] ??= [];
        $this->events[$name][] = new Listener($listener);
    }

    /**
     * @param string $name
     * @param Closure $listener
     */
    public function listenOnce(string $name, Closure $listener): void
    {
        $this->events[$name] ??= [];
        $this->events[$name][] = new Listener($listener, true);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasListeners(string $name): bool
    {
        return isset($this->events[$name]);
    }

    /**
     * @param Event $event
     * @param string|null $name
     */
    public function dispatch(Event $event, ?string $name = null): void
    {
        $name ??= get_class($event);

        if (!$this->hasListeners($name)) {
            return;
        }

        $listeners = $this->events[$name] ?? [];
        foreach ($listeners as $index => $listener) {
            $listener->invoke($event);

            if (!$listener->isListening()) {
                unset($listeners[$index]);
            }

            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }

    /**
     * @param string $name
     * @param Closure $targetListener
     */
    public function removeListener(string $name, Closure $targetListener): void
    {
        if (!$this->hasListeners($name)) {
            return;
        }

        $listeners = &$this->events[$name];
        foreach ($listeners as $index => $listener) {
            if ($listener === $targetListener) {
                unset($listeners[$index]);
            }
        }

        if (empty($listeners)) {
            $listeners = null;
        }
    }

    /**
     * @param string $name
     */
    public function removeListeners(string $name): void
    {
        $this->events[$name] = null;
    }
}
