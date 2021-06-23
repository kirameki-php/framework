<?php declare(strict_types=1);

namespace Kirameki\Exception\Handlers;

use Throwable;

interface HandlerInterface
{
    /**
     * @param Throwable $exception
     * @return void
     */
    public function handle(Throwable $exception): void;
}
