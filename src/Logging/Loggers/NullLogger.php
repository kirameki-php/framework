<?php declare(strict_types=1);

namespace Kirameki\Logging\Loggers;

use Kirameki\Logging\Concerns\HandlesLevels;
use Psr\Log\LoggerInterface;

class NullLogger implements LoggerInterface
{
    use HandlesLevels;

    /**
     * @param int $level
     * @param string $message
     * @param array<mixed> $context
     */
    public function log($level, $message, array $context = array()): void
    {
        // do nothing
    }
}
