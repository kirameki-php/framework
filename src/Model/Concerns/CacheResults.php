<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait CacheResults
{
    /**
     * @var array
     */
    protected array $resolvedProperties = [];

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    protected function cacheResolved(string $name, $value): static
    {
        $this->resolvedProperties[$name] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function clearResultCache(): static
    {
        $this->resolvedProperties = [];
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isResultCached(string $name): bool
    {
        return array_key_exists($name, $this->resolvedProperties);
    }
}
