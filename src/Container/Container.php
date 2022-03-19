<?php declare(strict_types=1);

namespace Kirameki\Container;

use Closure;
use Kirameki\Support\Collection;
use function array_key_exists;

class Container
{
    /**
     * @var array<class-string, Entry<mixed>>
     */
    protected array $entries = [];

    /**
     * @template TEntry
     * @param class-string<TEntry> $id
     * @return TEntry
     */
    public function get(string $id): mixed
    {
        return $this->entries[$id]->getInstance(); /** @phpstan-ignore-line */
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }

    /**
     * @param class-string $id
     * @param mixed $entry
     * @param bool $cached
     * @return void
     */
    public function set(string $id, mixed $entry, bool $cached = false): void
    {
        $this->entries[$id] = $entry instanceof Closure
            ? new ClosureEntry($id, $entry, [$this], $cached)
            : new InstanceEntry($id, $entry);
    }

    /**
     * @param class-string $id
     * @param mixed|callable(static): mixed $entry
     * @return void
     */
    public function singleton(string $id, mixed $entry): void
    {
        $this->set($id, $entry, true);
    }

    /**
     * @param class-string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        if ($this->has($id)) {
            unset($this->entries[$id]);
            return true;
        }
        return false;
    }

    /**
     * @template TEntry
     * @param class-string<TEntry> $id
     * @return Entry<TEntry>
     */
    public function entry(string $id): Entry
    {
        return $this->entries[$id]; /** @phpstan-ignore-line */
    }

    /**
     * @return Collection<class-string, Entry<mixed>>
     */
    public function entries(): Collection
    {
        return new Collection($this->entries); /** @phpstan-ignore-line */
    }
}
