<?php declare(strict_types=1);

namespace Kirameki\Logging\Loggers;

use Kirameki\Logging\Concerns\HandlesLevels;
use Psr\Log\LoggerInterface;

class MultiLogger implements LoggerInterface
{
    use HandlesLevels;

    /**
     * @var array<LoggerInterface>
     */
    protected array $loggers;

    /**
     * @param array<LoggerInterface> $loggers
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    /**
     * @param int $level
     * @param string $message
     * @param array<mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
