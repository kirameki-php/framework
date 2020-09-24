<?php

namespace Kirameki\Logging\Writers;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\DefaultScheme;
use Kirameki\Core\Application;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class FileWriter extends Logger
{
    public function __construct(array $options = [])
    {
        $name = $options['name'] ?? '';
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
        $level = $options['level'] ?? (Application::instance()->inDebugMode() ? LogLevel::NOTICE : LogLevel::DEBUG);

        $fileHandler = new StreamHandler($path, $level, $bubble, $permission);

        $colorSchemeClass = $options['color_scheme'] ?? DefaultScheme::class;
        $logFormat = $options['format'] ?? "[%datetime%] [%level_name%] %message%\n";
        $dateFormat = $options['date_format'] = 'Y-m-d H:i:s.v';
        $formatter = new ColoredLineFormatter(new $colorSchemeClass, $logFormat, $dateFormat, false, true);

        $fileHandler->setFormatter($formatter);

        return $fileHandler;
    }
}
