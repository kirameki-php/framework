<?php

namespace Exelion\Support\Concerns;

trait Tappable
{
    /**
     * @param callable  $callback
     * @return $this
     */
    public function tap(callable $callback)
    {
        $callback($this);
        return $this;
    }
}
