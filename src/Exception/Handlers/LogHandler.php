<?php

namespace Kirameki\Exception\Handlers;

use Throwable;

class LogHandler implements HandlerInterface
{
    public function handle(Throwable $exception): void
    {
        dump($exception);
        if ($this->shouldIgnore($exception)) {
            return;
        }
        $message = $exception->getMessage();
        $context = ['exception' => $exception] + $this->context($exception);
        logger()->error($message, $context);
    }

    protected function shouldIgnore(Throwable $exception): bool
    {
        return false;
    }

    protected function context(Throwable $exception): array
    {
        return [];
    }
}
