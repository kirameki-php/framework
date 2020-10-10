<?php

namespace Kirameki\Model\Concerns;

use Carbon\Carbon;
use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Timestamps
{
    protected bool $timestamps = true;

    public function usesTimestamps()
    {
        return $this->timestamps;
    }

    protected function renewTimestamps()
    {
        if (! $this->usesTimestamps()) {
            return $this;
        }

        $now = $this->freshTimestamp();

        $updatedAtName = static::getUpdatedAtName();
        if ($updatedAtName !== null && !$this->wasChanged($updatedAtName)) {
            $this->setAttribute($updatedAtName, $now);
        }

        $createdAtName = static::getCreatedAtName();
        if (! $this->exists && ! is_null($createdAtName) && ! $this->wasChanged($createdAtName)) {
            $this->setAttribute($createdAtName, $now);
        }

        return $this;
    }

    public function freshTimestamp()
    {
        return Carbon::now();
    }

    public static function getCreatedAtName()
    {
        return 'createdAt';
    }

    public static function getUpdatedAtName()
    {
        return 'updatedAt';
    }
}
