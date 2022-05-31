<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use function get_class;

class EventManager
{
    /**
     * @var array<string, list<Listener>>
     */
    protected array $events = [];

    /**
     * @var list<Closure>
     */
    protected array $onAdded = [];

    /**
     * @var list<Closure>
     */
    protected array $onRemoved = [];

    /**
     * @var list<Closure>
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

            if ($event instanceof StoppableEvent && $event->isPropagationStopped()) {
                break;
            }
        }

        $this->invokeCallbacks($this->onDispatched, $event, $name);
    }

    /**
     * @private
     * @param class-string<Event> $class
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
            if ($listener->getCallback() === $targetListener) {
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
        unset($this->events[$name]);
        $this->invokeCallbacks($this->onRemoved, $name, null);
    }

    /**
     * @param Closure $callback
     * @return void
     */
    public function onListenerAdded(Closure $callback): void
    {
        $this->onAdded[] = $callback;
    }

    /**
     * @param Closure $callback
     * @return void
     */
    public function onListenerRemoved(Closure $callback): void
    {
        $this->onRemoved[] = $callback;
    }

    /**
     * @param Closure $callback
     * @return void
     */
    public function onDispatched(Closure $callback): void
    {
        $this->onDispatched[] = $callback;
    }

    /**
     * @param array<int, Closure> $callbacks
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
