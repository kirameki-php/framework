<?php declare(strict_types=1);

namespace Kirameki\Support\Concerns;

trait Tappable
{
    /**
     * @param callable  $callback
     * @return $this
     */
    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }
}
