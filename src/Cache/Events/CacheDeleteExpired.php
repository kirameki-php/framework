<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

class CacheDeleteExpired extends CacheDeleted
{
    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param array<string> $keys
     */
    public function __construct(string $name, string $namespace, string $command, array $keys)
    {
        parent::__construct($name, $namespace, $command, $keys, []);
    }
}
