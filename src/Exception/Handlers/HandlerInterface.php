<?php


namespace Kirameki\Exception\Handlers;

use Throwable;

interface HandlerInterface
{
    public function handle(Throwable $exception): void;
}
