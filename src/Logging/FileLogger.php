<?php

namespace Kirameki\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class FileLogger extends Logger
{
    public function __construct(array $options = [])
    {
        $name = $options['name'] ?? 'file';
        parent::__construct($name, $this->createHandlers($options));
    }

    protected function createHandlers(array $options): array
    {
        return [
            $this->createStreamHandler($options)
        ];
    }

    protected function createStreamHandler(array $options): HandlerInterface
    {
        $path = $options['path'] ?? storage_path('logs/app.log');
        $permission = $options['permission'] ?? 0777;
        $bubble = $options['bubble'] ?? true;
        $level = $options['level'] ?? (app()->isProduction() ? LogLevel::NOTICE : LogLevel::DEBUG);

        $fileHandler = new StreamHandler($path, $level, $bubble, $permission);

        $logFormat = $options['format'] ?? "[%datetime%] [%level_name%] %message%\n";
        $dateFormat = $options['date_format'] = 'Y-m-d H:i:s.v';
        $formatter = new LineFormatter($logFormat, $dateFormat, false, true);

        $fileHandler->setFormatter($formatter);

        return $fileHandler;
    }
}
