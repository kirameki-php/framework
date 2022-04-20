<?php declare(strict_types=1);

namespace Kirameki\Exception;

use Closure;
use ErrorException;
use Kirameki\Exception\Handlers\Handler;
use Throwable;

class ExceptionManager
{
    /**
     * @var array<string, Handler|Closure>
     */
    protected array $registered;

    public function __construct()
    {
        $this->setErrorHandling();
        $this->setExceptionHandling();
        $this->setFatalHandling();
        $this->registered = [];
    }

    /**
     * @param string $name
     * @param Closure(): Handler $handler
     * @return void
     */
    public function setHandler(string $name, Closure $handler): void
    {
        $this->registered[$name] = $handler;
    }

    /**
     * @param class-string $name
     * @return void
     */
    public function removeHandler(string $name): void
    {
        unset($this->registered[$name]);
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    protected function handle(Throwable $exception): void
    {
        try {
            foreach ($this->registered as $name => $handler) {
                if ($handler instanceof Closure) {
                    $handler = $this->registered[$name] = $handler();
                }
                $handler->handle($exception);
            }
        }
        catch (Throwable $innerException) {
            $this->fallback($innerException);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        return [];
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    protected function fallback(Throwable $exception): void
    {
        error_log((string) $exception);
    }

    /**
     * @throws ErrorException
     * @return void
     */
    protected function setErrorHandling(): void
    {
        set_error_handler(static function(int $no, string $msg, string $file, int $line) {
            throw new ErrorException($msg, 0, $no, $file, $line);
        });
    }

    /**
     * @return void
     */
    protected function setExceptionHandling(): void
    {
        set_exception_handler(function (Throwable $throwable) {
            $this->handle($throwable);
        });
    }

    /**
     * @return void
     */
    protected function setFatalHandling(): void
    {
        register_shutdown_function(function() {
            if($err = error_get_last()) {
                $this->handle(new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
            }
        });
    }
}
