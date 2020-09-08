<?php

namespace Kirameki\Container;

use Closure;

class InstanceEntry implements EntryInterface
{
    protected string $id;

    protected $instance;

    public function __construct(string $id, $instance)
    {
        $this->id = $id;
        $this->instance = $instance;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstance()
    {
        return $this->instance;
    }

    public function onResolved(Closure $callback): void
    {
        $callback($this->instance);
    }
}
