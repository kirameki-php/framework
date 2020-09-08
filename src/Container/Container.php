<?php

namespace Kirameki\Container;

use Closure;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /** @var EntryInterface[] */
    protected array $entries = [];

    public function get($id)
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

    public function remove(string $id): void
    {
        unset($this->entries[$id]);
    }

    public function singleton(string $id, $entry): void
    {
        $this->set($id, $entry, true);
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
