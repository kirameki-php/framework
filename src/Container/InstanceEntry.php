<?php

namespace Kirameki\Container;

use Closure;

class InstanceEntry implements EntryInterface
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var mixed
     */
    protected $instance;

    /**
     * @param string $id
     * @param mixed $instance
     */
    public function __construct(string $id, $instance)
    {
        $this->id = $id;
        $this->instance = $instance;
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
        return $this->instance;
    }

    /**
     * @param Closure $callback
     */
    public function onResolved(Closure $callback): void
    {
        $callback($this->instance);
    }
}
