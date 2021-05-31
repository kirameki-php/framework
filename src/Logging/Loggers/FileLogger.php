<?php

namespace Kirameki\Logging\Loggers;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\DefaultScheme;
use Kirameki\Core\Application;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class FileLogger extends Logger
{
    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $name = $options['name'] ?? '';
        parent::__construct($name, $this->createHandlers($options));
    }

    /**
     * @param array $options
     * @return HandlerInterface[]
     */
    protected function createHandlers(array $options): array
    {
        return [
            $this->createStreamHandler($options)
        ];
    }

    /**
     * @param array $options
     * @return HandlerInterface
     */
    protected function createStreamHandler(array $options): HandlerInterface
    {
        $path = $options['path'] ?? storage_path('logs/app.log');
        $permission = $options['permission'] ?? 0777;
        $bubble = $options['bubble'] ?? true;
        $level = $options['level'] ?? LogLevel::DEBUG;
        $fileHandler = new StreamHandler($path, $level, $bubble, $permission);

        $colorSchemeClass = $options['color_scheme'] ?? DefaultScheme::class;
        $logFormat = $options['format'] ?? "[%datetime%] [%level_name%] %message%\n";
        $dateFormat = $options['date_format'] ?? 'Y-m-d H:i:s.v';
        $formatter = new ColoredLineFormatter(new $colorSchemeClass, $logFormat, $dateFormat, false, true);

        $fileHandler->setFormatter($formatter);

        return $fileHandler;
    }
}
