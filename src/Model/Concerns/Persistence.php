<?php

namespace Kirameki\Model\Concerns;

use Carbon\Carbon;
use Kirameki\Model\Model;
use Kirameki\Support\Json;

/**
 * @mixin Model
 */
trait Persistence
{
    /**
     * @var bool
     */
    public bool $persisted = false;

    /**
     * @var bool
     */
    public bool $deleted = false;

    /**
     * @return bool
     */
    public function isNewRecord(): bool
    {
        return ! $this->persisted;
    }

    /**
     * @return bool
     */
    public function isPersisted(): bool
    {
        return $this->persisted && !$this->deleted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return $this
     */
    protected function markAsPersisted()
    {
        $this->persisted = true;
        return $this;
    }

    /**
     * @return $this
     */
    protected function markAsDeleted()
    {
        $this->deleted = true;
        return $this;
    }
}
