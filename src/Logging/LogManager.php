<?php

namespace Kirameki\Logging;

use Closure;
use Kirameki\Core\Config;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager implements LoggerInterface
{
    use Concerns\HandlesLevels;

    protected Config $config;

    protected array $channels;

    protected array $loggers;

    protected LoggerInterface $default;

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

    public function channel(string $channel): LoggerInterface
    {
        return $this->channels[$channel] ??= $this->resolveChannel($channel);
    }

    protected function resolveChannel(string $name): LoggerInterface
    {
        $options = $this->config[$name];
        $resolver = $this->loggers[$options['logger']];
        return $resolver($options);
    }

    public function addLogger(string $name, Closure $resolver): static
    {
        $this->loggers[$name] = $resolver;
        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->default->log($level, $message, $context);
    }
}
