<?php declare(strict_types=1);

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

    /**
     * @template TClass
     * @param class-string<TClass> $id
     * @return TClass
     */
    public function get(string $id)
    {
        return $this->entries[$id]->getInstance();
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
     * @param string $id
     * @param $entry
     * @param bool $cached
     * @return void
     */
    public function set(string $id, $entry, bool $cached = false): void
    {
        $this->entries[$id] = $entry instanceof Closure
            ? new ClosureEntry($id, $entry, [$this], $cached)
            : new InstanceEntry($id, $entry);
    }

    /**
     * @param string $id
     * @param $entry
     * @return void
     */
    public function singleton(string $id, $entry): void
    {
        $this->set($id, $entry, true);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove(string $id): bool
    {
        if ($this->has($id)) {
            unset($this->entries[$id]);
            return true;
        }
        return false;
    }

    /**
     * @param string $id
     * @return EntryInterface
     */
    public function entry(string $id): EntryInterface
    {
        return $this->entries[$id];
    }

    /**
     * @return Collection<EntryInterface>
     */
    public function entries(): Collection
    {
        return new Collection($this->entries);
    }

    /**
     * @param string $id
     * @param Closure $callback
     * @return void
     */
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
