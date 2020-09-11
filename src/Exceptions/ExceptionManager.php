<?php

namespace Kirameki\Exceptions;

use ErrorException;
use Kirameki\Container\Container;
use Kirameki\Container\EntryInterface;
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
        //ini_set('display_errors', false);

        $this->setErrorHandling();
        $this->setExceptionHandling();
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
            $this->handlers->entries()
                ->map(fn(EntryInterface $entry) => $entry->getInstance())
                ->each(fn(HandlerInterface $handler) => $handler->handle($exception));
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

    protected function setErrorHandling(): void
    {
        set_error_handler(function(int $no, string $msg, string $file, int $line) {
            throw new ErrorException($msg, 0, $no, $file, $line);
        });
    }

    protected function setExceptionHandling(): void
    {
        set_exception_handler(function (Throwable $throwable) {
            $this->handle($throwable);
        });
    }

    protected function setFatalHandling(): void
    {
        register_shutdown_function(function() {
            if(($err = error_get_last()) && ($err['type'] & E_ERROR)) {
                $this->handle(new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
            }
        });
    }
}
