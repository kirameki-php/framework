<?php

namespace Kirameki\Logging\Loggers;

use Kirameki\Logging\Concerns\HandlesLevels;
use Kirameki\Logging\LogManager;
use Psr\Log\LoggerInterface;

class MultiLogger implements LoggerInterface
{
    use HandlesLevels;

    /**
     * @var LoggerInterface[]
     */
    protected array $loggers;

    /**
     * @param array $loggers
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
