<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

use Kirameki\Event\Event;

abstract class CacheEvent extends Event
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $namespace;

    /**
     * @var string
     */
    public string $command;

    /**
     * @param string $name
     * @param string $namespace
     * @param string $command
     */
    public function __construct(string $name, string $namespace, string $command)
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->command = $command;
    }
}
