<?php

namespace Kirameki\Logging;

use Kirameki\Container\Container;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager implements LoggerInterface
{
    protected Container $loggers;

    protected Logger $default;

    public function __construct()
    {
        $this->loggers = new Container();
    }

    public function setDefaultLogger(string $channel): void
    {
        $this->default = $this->channel($channel);
    }

    public function setLogger(string $channel, $logger): self
    {
        $this->loggers->singleton($channel, $logger);
        return $this;
    }

    public function channel(string $channel): Logger
    {
        return $this->loggers->get($channel);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->default->log($level, $message, $context);
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
