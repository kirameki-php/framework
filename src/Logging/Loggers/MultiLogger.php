<?php

namespace Kirameki\Logging\Loggers;

use Kirameki\Logging\Concerns\HandlesLevels;
use Kirameki\Logging\LogManager;
use Psr\Log\LoggerInterface;

class MultiLogger implements LoggerInterface
{
    use HandlesLevels;

    /**
     * @var LogManager
     */
    protected LogManager $logManager;

    /**
     * @var LoggerInterface[]
     */
    protected array $loggers;

    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function log($level, $message, array $context = array()): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
