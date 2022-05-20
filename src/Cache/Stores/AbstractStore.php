<?php declare(strict_types=1);

namespace Kirameki\Cache\Stores;

use Closure;
use DateInterval;
use DateTimeInterface;
use Kirameki\Cache\Events\CacheAccessed;
use Kirameki\Cache\Events\CacheChecked;
use Kirameki\Cache\Events\CacheCleared;
use Kirameki\Cache\Events\CacheCountUpdated;
use Kirameki\Cache\Events\CacheDeleted;
use Kirameki\Cache\Events\CacheDeleteExpired;
use Kirameki\Cache\Events\CacheDeleteMatched;
use Kirameki\Cache\Events\CacheStored;
use Kirameki\Event\Event;
use Kirameki\Event\EventManager;
use Kirameki\Support\Arr;
use function preg_replace;
use function time;

abstract class AbstractStore implements Store
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
    protected string $name;

    /**
     * @var string
     */
    protected string $namespace;

    /**
     * @var bool
     */
    protected bool $triggerEvents = true;

    /**
     * @var EventManager
     */
    protected EventManager $eventManager;

    /**
     * @param string $key
     * @param Closure $callback
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return mixed
     */
    public function remember(string $key, Closure $callback, DateTimeInterface|DateInterval|int|float|null $ttl = null): mixed
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
    public function formatKey(?string $key = null): string
    {
        return static::$prefix.$this->namespace.static::$delimiter.$key;
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    public function formatKeys(array $keys): array
    {
        return Arr::map($keys, fn($key) => $this->formatKey($key));
    }

    /**
     * @param string $key
     * @return string
     */
    public function deformatKey(string $key): string
    {
        return preg_replace('/'.static::$prefix.$this->namespace.static::$delimiter.'/', '', $key, 1);
    }

    /**
     * @param array<mixed> $results
     * @return list<string>
     */
    public function unformatKeys(array $results): array
    {
        return Arr::keyBy($results, fn($_, $key) => $this->deformatKey($key));
    }

    /**
     * @param array<string, mixed> $entries
     * @return array<string, mixed>
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
     * @return void
     */
    public function emitEvents(bool $toggle): void
    {
        $this->triggerEvents = $toggle;
    }

    /**
     * @param Event $event
     * @return void
     */
    protected function triggerEvent(Event $event): void
    {
        $this->eventManager->dispatch($event);
    }

    /**
     * @param string $command
     * @param array<string> $keys
     * @param array<string, mixed> $results
     * @return void
     */
    protected function triggerAccessEvent(string $command, array $keys, array $results): void
    {
        $this->triggerEvent(new CacheAccessed($this->name, $this->namespace, $command, $keys, $this->unformatKeys($results)));
    }

    /**
     * @param string $command
     * @param array<string, bool> $results
     * @return void
     */
    protected function triggerCheckEvent(string $command, array $results): void
    {
        $this->triggerEvent(new CacheChecked($this->name, $this->namespace, $command, $results));
    }

    /**
     * @param string $command
     * @param array<string, mixed> $entries
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return void
     */
    protected function triggerStoreEvent(string $command, array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        $this->triggerEvent(new CacheStored($this->name, $this->namespace, $command, $entries, $ttl));
    }

    /**
     * @param string $command
     * @param string $key
     * @param int $by
     * @param int|null $result
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return void
     */
    protected function triggerCounterEvent(string $command, string $key, int $by, ?int $result, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        $this->triggerEvent(new CacheCountUpdated($this->name, $this->namespace, $command, $key, $by, $result, $ttl));
    }

    /**
     * @param string $command
     * @param array<string> $keys
     * @param array<string> $missedKeys
     * @return void
     */
    protected function triggerDeleteEvent(string $command, array $keys, array $missedKeys): void
    {
        $this->triggerEvent(new CacheDeleted($this->name, $this->namespace, $command, $keys, $missedKeys));
    }

    /**
     * @param string $command
     * @param string $pattern
     * @param list<string> $keys
     */
    protected function triggerDeleteMatchedEvent(string $command, string $pattern, array $keys): void
    {
        $this->triggerEvent(new CacheDeleteMatched($this->name, $this->namespace, $command, $pattern, $keys));
    }

    /**
     * @param string $command
     * @param list<string> $keys
     */
    protected function triggerDeleteExpiredEvent(string $command, array $keys): void
    {
        $this->triggerEvent(new CacheDeleteExpired($this->name, $this->namespace, $command, $keys));
    }

    /**
     * @param string $command
     * @return void
     */
    protected function triggerClearEvent(string $command): void
    {
        $this->triggerEvent(new CacheCleared($this->name, $this->namespace, $command));
    }

    /**
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @param int|null $now
     * @return int|float|null
     */
    protected function formatTtl(DateTimeInterface|DateInterval|int|float|null $ttl = null, ?int $now = null): int|float|null
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
