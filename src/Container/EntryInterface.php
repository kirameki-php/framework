<?php

namespace Kirameki\Container;

use Closure;

interface EntryInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return mixed
     */
    public function getInstance();

    /**
     * @param Closure $callback
     * @return void
     */
    public function onResolved(Closure $callback): void;
}
