<?php

namespace Kirameki\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;

abstract class AbstractStore implements StoreInterface
{
    protected static string $delimiter = ':';
    protected static string $prefix = 'cache:';
    protected string $namespace;

    protected bool $triggerEvents = true;

    public function remember(string $key, Closure $callback, ?int $ttl = null)
    {
        $value = null;
        if ($this->tryGet($key, $value)) {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function formatKey(string $key = null): string
    {
        return static::$prefix.$this->namespace.static::$delimiter.$key;
    }

    public function formatKeys(array $keys): array
    {
        return array_map(fn($key) => $this->formatKey($key), $keys);
    }

    public function formatEntries(array $entries): array
    {
        $prefixed = [];
        foreach ($entries as $key => $value) {
            $prefixed[$this->formatKey($key)] = $value;
        }
        return $prefixed;
    }

    public function triggerEvents(bool $toggle): void
    {
        $this->triggerEvents = $toggle;
    }

    protected function triggerEvent(string $name)
    {
        // TODO implement event dispatcher
    }

    protected function formatTtl($ttl = null, int $now = null): int
    {
        if ($ttl instanceof DateTimeInterface) {
            return $ttl->getTimestamp() - ($now ?? time());
        }
        if ($ttl instanceof DateInterval) {
            return ($ttl->days * 86400 + $ttl->h * 3600 + $ttl->i * 60 + $ttl->s)
                 * ($ttl->invert === 1 ? -1 : 1);
        }
        return $ttl;
    }
}
