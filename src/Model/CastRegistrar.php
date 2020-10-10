<?php

namespace Kirameki\Model;

use Closure;
use Kirameki\Model\Casts\CastInterface;

class CastRegistrar
{
    /**
     * @var CastInterface[]
     */
    protected array $casts = [];

    /**
     * @var Closure[]
     */
    protected array $deferred = [];

    /**
     * @param string $name
     * @return CastInterface
     */
    public function get(string $name): CastInterface
    {
        return $this->casts[$name] ??= call_user_func($this->deferred[$name]);
    }

    /**
     * @param string $name
     * @param Closure $deferred
     * @return $this
     */
    public function add(string $name, Closure $deferred)
    {
        $this->deferred[$name] = $deferred;
        return $this;
    }
}
