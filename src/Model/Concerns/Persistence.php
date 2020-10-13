<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

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
     * @return bool
     */
    public function delete(): bool
    {
        $count = $this->getConnection()
            ->delete($this->getTable())
            ->where($this->getPrimaryKeyName(), $this->getPrimaryKey())
            ->execute();

        $this->deleted = true;

        return $count === 1;
    }
}
