<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

use DateInterval;
use DateTimeInterface;

class CacheStored extends CacheEvent
{
    /**
     * @var array
     */
    public array $entries = [];

    /**
     * @var DateTimeInterface|DateInterval|int|float|null
     */
    public DateTimeInterface|DateInterval|int|float|null $ttl;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param array $entries
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     */
    public function __construct(string $name, string $namespace, string $command, array $entries, DateTimeInterface|DateInterval|int|float|null $ttl)
    {
        parent::__construct($name, $namespace, $command);
        $this->entries = $entries;
        $this->ttl = $ttl;
    }
}
