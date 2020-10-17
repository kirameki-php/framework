<?php

namespace Kirameki\Logging;

use Closure;
use Kirameki\Core\Config;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager implements LoggerInterface
{
    protected Config $config;

    protected array $channels;

    protected array $loggers;

    protected Logger $default;

    public function __construct(Config $config)
    {
        $this->config = $config->dig('channels');
        $this->channels = [];
        $this->loggers = [];
    }

    public function setDefaultChannel(string $channel): void
    {
        $this->default = $this->channel($channel);
    }

    public function channel(string $channel): Logger
    {
        return $this->channels[$channel] ??= $this->resolveChannel($channel);
    }

    protected function resolveChannel(string $name): Logger
    {
        $options = $this->config[$name];
        $resolver = $this->loggers[$options['logger']];
        return $resolver($options);
    }

    public function setLogger(string $name, Closure $resolver): self
    {
        $this->loggers[$name] = $resolver;
        return $this;
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
