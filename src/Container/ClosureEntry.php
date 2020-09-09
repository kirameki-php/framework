<?php

namespace Kirameki\Container;

use Closure;

class ClosureEntry implements EntryInterface
{
    protected string $id;

    protected Closure $resolver;

    protected bool $cacheable;

    protected bool $resolved;

    protected $instance;

    protected array $onResolvedCallbacks = [];

    public function __construct(string $id, Closure $resolver, bool $cacheable)
    {
        $this->id = $id;
        $this->resolver = $resolver;
        $this->cacheable = $cacheable;
        $this->resolved = false;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstance()
    {
        if (!$this->cacheable) {
            $instance = call_user_func($this->resolver);
            $this->invokeOnResolved($instance);
            return $instance;
        }

        if (!$this->resolved) {
            $this->instance = call_user_func($this->resolver);
            $this->resolved = true;
            $this->invokeOnResolved($this->instance);
        }

        return $this->instance;
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
