<?php

namespace Kirameki\Logging;

use Closure;
use Kirameki\Core\Config;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager implements LoggerInterface
{
    use Concerns\HandlesLevels;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var array
     */
    protected array $channels;

    /**
     * @var array
     */
    protected array $loggers;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $default;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config->extract('channels');
        $this->channels = [];
        $this->loggers = [];
    }

    /**
     * @param string $channel
     * @return void
     */
    public function setDefaultChannel(string $channel): void
    {
        $this->default = $this->channel($channel);
    }

    /**
     * @param string $channel
     * @return LoggerInterface
     */
    public function channel(string $channel): LoggerInterface
    {
        return $this->channels[$channel] ??= $this->resolveChannel($channel);
    }

    /**
     * @param string $name
     * @return LoggerInterface
     */
    protected function resolveChannel(string $name): LoggerInterface
    {
        $options = $this->config[$name];
        $resolver = $this->loggers[$options['logger']];
        return $resolver($options);
    }

    /**
     * @param string $name
     * @param Closure $resolver
     * @return $this
     */
    public function addLogger(string $name, Closure $resolver): static
    {
        $this->loggers[$name] = $resolver;
        return $this;
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->default->log($level, $message, $context);
    }
}
