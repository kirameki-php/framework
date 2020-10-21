<?php

namespace Kirameki\Logging\Loggers;

use Kirameki\Logging\Concerns\HandlesLevels;
use Psr\Log\LoggerInterface;

class NullLogger implements LoggerInterface
{
    use HandlesLevels;

    public function __construct()
    {
    }

    public function log($level, $message, array $context = array()): void
    {
        // do nothing
    }
}
