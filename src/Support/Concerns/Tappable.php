<?php declare(strict_types=1);

namespace Kirameki\Support\Concerns;

use Closure;

trait Tappable
{
    /**
     * @param Closure($this): mixed $callback
     * @return $this
     */
    public function tap(Closure $callback): static
    {
        $callback($this);
        return $this;
    }
}
