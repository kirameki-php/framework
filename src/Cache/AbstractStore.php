<?php

namespace Kirameki\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Kirameki\Support\Arr;

abstract class AbstractStore implements StoreInterface
{
    /**
     * @var string
     */
    protected static string $delimiter = ':';

    /**
     * @var string
     */
    protected static string $prefix = 'cache:';

    /**
     * @var string
     */
    protected string $namespace;

    /**
     * @var bool
     */
    protected bool $triggerEvents = true;

    /**
     * @param string $key
     * @param Closure $callback
     * @param DateTimeInterface|int|null $ttl
     * @return mixed
     */
    public function remember(string $key, Closure $callback, DateTimeInterface|int $ttl = null): mixed
    {
        $value = null;
        if ($this->tryGet($key, $value)) {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * @return string
     */
    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string|null $key
     * @return string
     */
    public function formatKey(string $key = null): string
    {
        return static::$prefix.$this->namespace.static::$delimiter.$key;
    }

    /**
     * @param array $keys
     * @return array
     */
    public function formatKeys(array $keys): array
    {
        return Arr::map($keys, fn($key) => $this->formatKey($key));
    }

    /**
     * @param array $entries
     * @return array
     */
    public function formatEntries(array $entries): array
    {
        $prefixed = [];
        foreach ($entries as $key => $value) {
            $prefixed[$this->formatKey($key)] = $value;
        }
        return $prefixed;
    }

    /**
     * @param bool $toggle
     */
    public function emitEvents(bool $toggle): void
    {
        $this->triggerEvents = $toggle;
    }

    /**
     * @param string $name
     */
    protected function triggerEvent(string $name)
    {
        // TODO implement event dispatcher
    }

    /**
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @param int|null $now
     * @return int
     */
    protected function formatTtl(DateTimeInterface|DateInterval|int|float|null $ttl = null, int $now = null): int
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
