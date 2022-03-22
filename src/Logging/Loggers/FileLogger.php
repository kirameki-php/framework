<?php declare(strict_types=1);

namespace Kirameki\Logging\Loggers;

use Kirameki\Logging\Formatters\ColoredLineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class FileLogger extends Logger
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->path = $options['path'] ?? storage_path('logs/app.log');

        $name = $options['name'] ?? '';

        parent::__construct($name, $this->createHandlers($options));
    }

    /**
     * @param array<string, mixed> $options
     * @return HandlerInterface[]
     */
    protected function createHandlers(array $options): array
    {
        return [
            $this->createStreamHandler($options)
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return HandlerInterface
     */
    protected function createStreamHandler(array $options): HandlerInterface
    {
        $permission = $options['permission'] ?? 0777;
        $bubble = $options['bubble'] ?? true;
        $level = $options['level'] ?? LogLevel::DEBUG;
        $fileHandler = new StreamHandler($this->path, $level, $bubble, $permission);

        $logFormat = $options['format'] ?? "[%datetime%] [%level_name%] %message%\n";
        $dateFormat = $options['date_format'] ?? 'Y-m-d H:i:s.v';
        $formatter = new ColoredLineFormatter($logFormat, $dateFormat, false, true);

        $fileHandler->setFormatter($formatter);

        return $fileHandler;
    }
}
