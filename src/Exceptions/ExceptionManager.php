<?php

namespace Kirameki\Exceptions;

use Kirameki\Container\Container;
use Kirameki\Exceptions\Handlers\HandlerInterface;
use Throwable;

class ExceptionManager
{
    protected Container $handlers;

    public function __construct()
    {
        // Report all PHP errors
        error_reporting(-1);

        // Don't show errors to user
        ini_set('display_errors', false);

        $this->setGlobalHandling();
        $this->setFatalHandling();

        $this->handlers = new Container();
    }

    public function setHandler(string $name, $handler): void
    {
        $this->handlers->singleton($name, $handler);
    }

    public function removeHandler(string $name): bool
    {
        return $this->handlers->remove($name);
    }

    protected function handle(Throwable $exception): void
    {
        try {
            $this->handlers->each(static function(HandlerInterface $handler) use ($exception) {
                $handler->handle($exception);
            });
        }
        catch (Throwable $innerException) {
            $this->fallback($innerException);
        }
    }

    protected function context(): array
    {
        return [];
    }

    protected function fallback(Throwable $exception): void
    {
        error_log((string) $exception);
    }

    protected function setGlobalHandling(): void
    {
        set_exception_handler([$this, 'handle']);
    }

    protected function setFatalHandling(): void
    {
        register_shutdown_function(function() {
            if(($error = error_get_last()) && $error['type'] === E_ERROR) {
                $this->handle(new FatalError($error));
            }
        });
    }
}
