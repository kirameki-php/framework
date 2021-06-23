<?php declare(strict_types=1);

namespace Kirameki\Logging\Loggers;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\DefaultScheme;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

class StdoutLogger extends Logger
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
        $bubble = $options['bubble'] ?? true;
        $level = $options['level'] ?? LogLevel::DEBUG;
        $fileHandler = new StreamHandler('php://stdout', $level, $bubble);

        $colorSchemeClass = $options['color_scheme'] ?? DefaultScheme::class;
        $logFormat = $options['format'] ?? "[%datetime%] [%level_name%] %message%\n";
        $dateFormat = $options['date_format'] = 'Y-m-d H:i:s.v';
        $formatter = new ColoredLineFormatter(new $colorSchemeClass, $logFormat, $dateFormat, false, true);

        $fileHandler->setFormatter($formatter);

        return $fileHandler;
    }
}
