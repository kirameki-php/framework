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
     * @param Event $event
     */
    public function dispatch(Event $event): void
    {
        $className = get_class($event);

        if (!isset($this->events[$className])) {
            return;
        }

        $listeners = $this->events[$className] ?? [];
        foreach ($listeners as $index => $listener) {
            $listener->invoke($event);

            if (!$listener->isListening()) {
                unset($listeners[$index]);
            }

            if ($listener->isPropagationStopped()) {
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
        if (!isset($this->events[$class])) {
            return;
        }

        $listeners = &$this->events[$class];
        foreach ($listeners as $index => $listener) {
            if ($listener === $targetListener) {
                unset($listeners[$index]);
            }
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
