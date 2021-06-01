<?php

namespace Kirameki\Cache\Events;

class CacheDeleteMatched extends CacheDeleted
{
    /**
     * @var string
     */
    public string $pattern;

    /**
     * @var string[]
     */
    public array $keys;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param string $pattern
     * @param array $keys
     */
    public function __construct(string $name, string $namespace, string $command, string $pattern, array $keys)
    {
        parent::__construct($name, $namespace, $command, $keys, []);
        $this->pattern = $pattern;
    }
}
