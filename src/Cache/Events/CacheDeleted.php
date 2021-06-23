<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

class CacheDeleted extends CacheEvent
{
    /**
     * @var string[]
     */
    public array $keys;

    /**
     * @var string[]
     */
    public array $missedKeys;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param array $keys
     * @param array $missedKeys
     */
    public function __construct(string $name, string $namespace, string $command, array $keys, array $missedKeys)
    {
        parent::__construct($name, $namespace, $command);
        $this->keys = $keys;
        $this->missedKeys = $missedKeys;
    }
}
