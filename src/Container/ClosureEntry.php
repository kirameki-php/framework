<?php

namespace Kirameki\Container;

use Closure;

class ClosureEntry implements EntryInterface
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var Closure
     */
    protected Closure $resolver;

    /**
     * @var bool
     */
    protected bool $cacheable;

    /**
     * @var bool
     */
    protected bool $resolved;

    /**
     * @var mixed|null
     */
    protected $instance;

    /**
     * @var Closure[]
     */
    protected array $onResolvedCallbacks = [];

    /**
     * @param string $id
     * @param Closure $resolver
     * @param bool $cacheable
     */
    public function __construct(string $id, Closure $resolver, bool $cacheable)
    {
        $this->id = $id;
        $this->resolver = $resolver;
        $this->cacheable = $cacheable;
        $this->resolved = false;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
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

    /**
     * @return bool
     */
    public function cached(): bool
    {
        return $this->resolved;
    }

    /**
     * @param Closure $callback
     */
    public function onResolved(Closure $callback): void
    {
        $this->onResolvedCallbacks ??= [];
        $this->onResolvedCallbacks[] = $callback;
    }

    /**
     * @param mixed|null $instance
     */
    protected function invokeOnResolved($instance): void
    {
        foreach ($this->onResolvedCallbacks as $callback) {
            $callback($instance);
        }
    }
}
