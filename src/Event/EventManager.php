<?php

namespace Kirameki\Event;

use Closure;

class EventManager
{
    /**
     * @var Listener[][]
     */
    protected array $events = [];

    /**
     * @var callable[]
     */
    protected array $onAdded = [];

    /**
     * @var callable[]
     */
    protected array $onRemoved = [];

    /**
     * @var callable[]
     */
    protected array $onDispatched = [];

    /**
     * @param string $name
     * @param Closure $listener
     * @param bool $once
     * @return void
     */
    public function listen(string $name, Closure $listener, bool $once = false): void
    {
        $this->events[$name] ??= [];
        $this->events[$name][] = new Listener($listener, $once);
        $this->invokeCallbacks($this->onAdded, $name, $listener, $once);
    }

    /**
     * @param string $name
     * @param Closure $listener
     * @return void
     */
    public function listenOnce(string $name, Closure $listener): void
    {
        $this->listen($name, $listener, true);
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
     * @return void
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

        $this->invokeCallbacks($this->onDispatched, $event, $name);
    }

    /**
     * @private
     * @param string $class
     * @param mixed ...$args
     * @return void
     */
    public function dispatchClass(string $class, ...$args): void
    {
        if ($this->hasListeners($class)) {
            $this->dispatch(new $class(...$args));
        }
    }

    /**
     * @param string $name
     * @param Closure $targetListener
     * @return void
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
            unset($this->events[$name]);
        }

        $this->invokeCallbacks($this->onRemoved, $name, $targetListener);
    }

    /**
     * @param string $name
     * @return void
     */
    public function removeListeners(string $name): void
    {
        $this->events[$name] = null;
        $this->invokeCallbacks($this->onRemoved, $name, null);
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function onListenerAdded(callable $callback): void
    {
        $this->onAdded[] = $callback;
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function onListenerRemoved(callable $callback): void
    {
        $this->onRemoved[] = $callback;
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function onDispatched(callable $callback): void
    {
        $this->onDispatched[] = $callback;
    }

    /**
     * @param array $callbacks
     * @param mixed ...$args
     * @return void
     */
    protected function invokeCallbacks(array $callbacks, ...$args): void
    {
        if (!empty($callbacks)) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }
}
