<?php declare(strict_types=1);

namespace Kirameki\Logging\Concerns;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * @mixin LoggerInterface
 */
trait HandlesLevels
{
    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /**
     * @param $message
     * @param array<mixed> $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(Logger::DEBUG, $message, $context);
    }
}
