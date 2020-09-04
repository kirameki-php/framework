<?php

namespace Kirameki\Container;

use Closure;

class ClosureEntry implements EntryInterface
{
    protected string $id;

    protected Closure $entry;

    protected bool $cacheable;

    protected bool $resolved;

    protected $cachedInstance;

    protected array $onResolvedCallbacks = [];

    public function __construct(string $id, Closure $entry, bool $cacheable)
    {
        $this->id = $id;
        $this->entry = $entry;
        $this->cacheable = $cacheable;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstance()
    {
        if (!$this->cacheable) {
            $instance = call_user_func($this->entry);
            $this->invokeOnResolved($instance);
            return $instance;
        }

        if (!$this->resolved) {
            $this->cachedInstance = call_user_func($this->entry);
            $this->resolved = true;
            $this->invokeOnResolved($this->cachedInstance);
        }

        return $this->cachedInstance;
    }

    public function cached(): bool
    {
        return $this->resolved;
    }

    public function onResolved(Closure $callback): void
    {
        $this->onResolvedCallbacks ??= [];
        $this->onResolvedCallbacks[] = $callback;
    }

    protected function invokeOnResolved($instance): void
    {
        foreach ($this->onResolvedCallbacks as $callback) {
            $callback($instance);
        }
    }
}
