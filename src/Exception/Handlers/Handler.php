<?php declare(strict_types=1);

namespace Kirameki\Exception\Handlers;

use Throwable;

interface Handler
{
    /**
     * @param Throwable $exception
     * @return void
     */
    public function handle(Throwable $exception): void;
}
