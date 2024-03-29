<?php declare(strict_types=1);

namespace Kirameki\Cache\Events;

class CacheDeleteMatched extends CacheDeleted
{
    /**
     * @var string
     */
    public readonly string $pattern;

    /**
     * @var array<string>
     */
    public readonly array $keys;

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
