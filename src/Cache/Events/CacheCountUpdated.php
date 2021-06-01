<?php

namespace Kirameki\Cache\Events;

use DateInterval;
use DateTimeInterface;

class CacheCountUpdated extends CacheStored
{
    /**
     * @var int
     */
    public int $result;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param string $key
     * @param int $by
     * @param int $result
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     */
    public function __construct(string $name, string $namespace, string $command, string $key, int $by, int $result, DateTimeInterface|DateInterval|int|float|null $ttl)
    {
        parent::__construct($name, $namespace, $command, [$key => $by], $ttl);
        $this->result = $result;
    }
}
