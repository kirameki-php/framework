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
     * @param string $class
     * @param Closure $listener
     */
    public function listen(string $class, Closure $listener): void
    {
        $this->events[$class] ??= [];
        $this->events[$class][] = new Listener($listener);
    }

    /**
     * @param string $class
     * @param Closure $listener
     */
    public function listenOnce(string $class, Closure $listener): void
    {
        $this->events[$class] ??= [];
        $this->events[$class][] = new Listener($listener, true);
    }

    /**
     * @param string $class
     * @return bool
     */
    public function hasListeners(string $class): bool
    {
        return isset($this->events[$class]);
    }

    /**
     * @param Event $event
     */
    public function dispatch(Event $event): void
    {
        $class = get_class($event);

        if (!$this->hasListeners($class)) {
            return;
        }

        $listeners = $this->events[$class] ?? [];
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
     * @param string $class
     * @param Closure $targetListener
     */
    public function removeListener(string $class, Closure $targetListener): void
    {
        if (!$this->hasListeners($class)) {
            return;
        }

        $listeners = &$this->events[$class];
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
     * @param string $class
     */
    public function removeListeners(string $class): void
    {
        $this->events[$class] = null;
    }
}
