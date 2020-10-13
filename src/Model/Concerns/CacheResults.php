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
    protected function cacheResult(string $name, $value)
    {
        $this->resolved[$name] = $value;
        return $this;
    }
}
