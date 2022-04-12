<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

class CacheChecked extends CacheEvent
{
    /**
     * @var array<string, bool>
     */
    public readonly array $entries;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param array<string, bool> $entries
     */
    public function __construct(string $name, string $namespace, string $command, array $entries)
    {
        parent::__construct($name, $namespace, $command);
        $this->entries = $entries;
    }
}
