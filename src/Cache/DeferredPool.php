<?php

namespace Kirameki\Cache;

class DeferredPool extends MemoryStore
{
    /**
     * @param string|null $namespace
     */
    public function __construct(?string $namespace = null)
    {
        parent::__construct($namespace);
    }

    /**
     * Since the pool keeps all removed data in the array until it's committed,
     * we need to check the extra [exists] metadata to see if it exists logically.
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return isset($this->stored[$key]) && $this->stored[$key]['exists'];
    }

    /**
     * Instead of actually removing the keys, we will only mark it as gone so that
     * Deferred store knows it existed in pool at some point.
     *
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool
    {
        if (isset($this->stored[$key])) {
            $this->stored[$key]['exists'] = false;
        }
        return true;
    }

    /**
     * MemoryStore tries to delete expired ones but deferred needs expired information
     * because if the record does not exist it will try to get it from the underlying
     * store where the data might still exist.
     *
     * @param $key
     * @param bool $deserialize
     * @return array|null
     */
    protected function fetchEntry($key, bool $deserialize = true): ?array
    {
        return $this->stored[$key] ?? null;
    }

    /**
     * @param $value
     * @param int|null $created
     * @param int|null $ttl
     * @return bool[]
     */
    protected function makeEntry($value, ?int $created, ?int $ttl): array
    {
        return parent::makeEntry($value, $created, $ttl) + ['exists' => true];
    }
}
