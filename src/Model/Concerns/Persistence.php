<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;
use Kirameki\Model\Relations\RelationCollection;

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
     * @return $this
     */
    public function save()
    {
        $conn = $this->getConnection();
        if ($this->isNewRecord()) {
            $conn->insertInto($this->getTable())
                ->value($this->getProperties())
                ->execute();
        } else {
            $conn->update($this->getTable())
                ->set($this->getProperties())
                ->execute();
        }

        foreach ($this->getRelations() as $name => $relation) {
            if ($relation instanceof Model) {
                $relation->save();
            }
            elseif ($relation instanceof RelationCollection) {
                $relation->saveAll();
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isNewRecord(): bool
    {
        return !$this->persisted && !$this->deleted;
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
    public function delete(): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        $count = $this->getConnection()
            ->delete($this->getTable())
            ->where($this->getPrimaryKeyName(), $this->getPrimaryKey())
            ->execute();

        $this->deleted = true;

        return $count === 1;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }
}
