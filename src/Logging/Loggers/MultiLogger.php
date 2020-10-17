<?php

namespace Kirameki\Logging\Loggers;

use Kirameki\Core\Config;
use Kirameki\Logging\LogManager;
use Kirameki\Support\Arr;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class MultiLogger implements LoggerInterface
{
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

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
