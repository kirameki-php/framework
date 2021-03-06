<?php declare(strict_types=1);

namespace Kirameki\Container;

use Closure;
use Kirameki\Support\Collection;
use Psr\Container\ContainerInterface;
use function array_key_exists;

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
    public function get(string $id): mixed
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
    public function delete(string $id): bool
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
}
