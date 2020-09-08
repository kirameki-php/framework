<?php

namespace Kirameki\Container;

use Closure;

interface EntryInterface
{
    public function getId(): string;

    public function getInstance();

    public function onResolved(Closure $callback): void;
}
