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
    protected array $resolved = [];

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    protected function cacheResolved(string $name, $value)
    {
        $this->resolved[$name] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function clearResultCache()
    {
        $this->resolved = [];
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isResultCached(string $name): bool
    {
        return array_key_exists($name, $this->resolved);
    }
}
