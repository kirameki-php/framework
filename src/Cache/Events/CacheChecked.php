<?php

namespace Kirameki\Cache\Events;

class CacheChecked extends CacheEvent
{
    /**
     * @var array
     */
    public array $entries;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     * @param array $entries
     */
    public function __construct(string $name, string $namespace, string $command, array $entries)
    {
        parent::__construct($name, $namespace, $command);
        $this->entries = $entries;
    }
}
