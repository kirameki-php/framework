<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

use Kirameki\Event\Event;

abstract class CacheEvent extends Event
{
    /**
     * @var string
     */
    public readonly string $name;

    /**
     * @var string
     */
    public readonly string $namespace;

    /**
     * @var string
     */
    public readonly string $command;

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
