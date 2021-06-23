<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

class CacheAccessed extends CacheEvent
{
    /**
     * @var string[]
     */
    public array $keys;

    /**
     * @var array
     */
    public array $results;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param string[] $keys
     * @param array $results
     */
    public function __construct(string $name, string $namespace, string $command, array $keys, array $results)
    {
        parent::__construct($name, $namespace, $command);
        $this->keys = $keys;
        $this->results = $results;
    }

    /**
     * @return string[]
     */
    public function hitKeys(): array
    {
        return array_keys($this->results);
    }

    /**
     * @return string[]
     */
    public function missedKeys(): array
    {
        return array_diff($this->keys, $this->hitKeys());
    }
}
