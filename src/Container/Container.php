<?php

namespace Kirameki\Container;

use Closure;
use Kirameki\Database\DatabaseManager;
use Kirameki\Support\Collection;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var EntryInterface[]
     */
    protected array $entries = [];

    public function get(mixed $id)
    {
        return $this->entries[$id]->getInstance();
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->entries);
    }

    public function set(string $id, $entry, bool $cached = false): void
    {
        $this->entries[$id] = $entry instanceof Closure
            ? new ClosureEntry($id, $entry, $cached)
            : new InstanceEntry($id, $entry);
    }

    public function singleton(string $id, $entry): void
    {
        $this->set($id, $entry, true);
    }

    public function remove(string $id): bool
    {
        if ($this->has($id)) {
            unset($this->entries[$id]);
            return true;
        }
        return false;
    }

    public function entry(string $id): EntryInterface
    {
        return $this->entries[$id];
    }

    /**
     * @return Collection|EntryInterface[]
     */
    public function entries()
    {
        return new Collection($this->entries);
    }

    public function onResolved(string $id, Closure $callback): void
    {
        $entry = $this->entries[$id];
        if ($entry instanceof ClosureEntry) {
            $entry->onResolved($callback);
        } else {
            $callback($entry);
        }
    }
}
