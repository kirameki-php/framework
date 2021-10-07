<?php declare(strict_types=1);

namespace Kirameki\Exception\Handlers;

use Symfony\Component\VarDumper\VarDumper;
use Throwable;

class VardumpHandler implements HandlerInterface
{
    /**
     * @param Throwable $exception
     * @return void
     */
    public function handle(Throwable $exception): void
    {
        VarDumper::dump($exception);
    }
}
