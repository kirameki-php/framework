<?php declare(strict_types=1);

namespace Kirameki\Cache\Stores;

class DeferredPool extends MemoryStore
{
    /**
     * @param string|null $namespace
     */
    public function __construct(?string $namespace = null)
    {
        parent::__construct($namespace);
        $this->emitEvents(false);
    }

    /**
     * Since the pool keeps all removed data in the array until it's committed,
     * we need to check the extra [exists] metadata to see if it exists logically.
     *
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        return isset($this->stored[$key]) && $this->stored[$key]['exists'];
    }

    /**
     * Instead of actually removing the keys, we will only mark it as gone so that
     * Deferred store knows it existed in pool at some point.
     *
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        if (isset($this->stored[$key])) {
            $this->stored[$key]['exists'] = false;
        }
    }

    /**
     * MemoryStore tries to delete expired ones but deferred needs the expired information
     * because if the record does not exist it will try to get it from the underlying
     * store where the data might still exist.
     *
     * @inheritDoc
     */
    protected function fetchEntry($key, bool $deserialize = true): ?array
    {
        return $this->stored[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    protected function makeEntry($value, ?int $created, ?int $ttl): array
    {
        return parent::makeEntry($value, $created, $ttl) + ['exists' => true];
    }
}
