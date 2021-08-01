<?php declare(strict_types=1);

namespace Kirameki\Exception\Handlers;

use Throwable;

class LogHandler implements HandlerInterface
{
    /**
     * @param Throwable $exception
     * @return void
     */
    public function handle(Throwable $exception): void
    {
        if ($this->shouldIgnore($exception)) {
            return;
        }
        $message = $exception->getMessage();
        $context = ['exception' => $exception] + $this->context($exception);
        logger()->error($message, $context);
    }

    /**
     * @param Throwable $exception
     * @return bool
     */
    protected function shouldIgnore(Throwable $exception): bool
    {
        return false;
    }

    /**
     * @param Throwable $exception
     * @return array
     */
    protected function context(Throwable $exception): array
    {
        return [];
    }
}
