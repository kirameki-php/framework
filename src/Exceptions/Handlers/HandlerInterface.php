<?php


namespace Kirameki\Exceptions\Handlers;

use Throwable;

interface HandlerInterface
{
    public function handle(Throwable $exception): void;
}
