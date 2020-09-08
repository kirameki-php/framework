<?php

namespace Kirameki\Cache;

use Closure;

abstract class AbstractStore implements StoreInterface
{
    public const PREFIX_DELIMITER = ':';

    protected string $prefix;

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

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function formatKey(string $key = null): string
    {
        return $this->prefix.self::PREFIX_DELIMITER.$key;
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

    }
}
