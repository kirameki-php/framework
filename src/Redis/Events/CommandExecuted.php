<?php declare(strict_types=1);

namespace Kirameki\Redis\Events;

use Kirameki\Event\Event;
use Kirameki\Redis\Connection;

class CommandExecuted extends Event
{
    /**
     * @param Connection $connection
     * @param string $command
     * @param array<mixed> $args
     * @param array<mixed> $result
     * @param float $execTimeMs
     */
    public function __construct(
        public readonly Connection $connection,
        public readonly string $command,
        public readonly array $args,
        public readonly array $result,
        public readonly float $execTimeMs,
    )
    {
    }
}
