<?php

namespace Kirameki\Model\Concerns;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Model\Model;
use Kirameki\Model\Relations\RelationCollection;
use RuntimeException;

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
     * @var bool
     */
    protected bool $processing = false;

    /**
     * @return $this
     */
    public function save()
    {
        if ($this->isDeleted()) {
            throw new RuntimeException(sprintf('Trying to save record which was deleted! (%s:%s)',
                $this->getTable(),
                $this->getPrimaryKey())
            );
        }

        $this->processing(function(Connection $conn) {
            $table = $this->getTable();
            $properties = $this->getProperties();

            $this->isNewRecord()
                ? $conn->insertInto($table)->value($properties)->execute()
                : $conn->update($table)->set($properties)->execute();

            foreach ($this->getRelations() as $relation) {
                if ($relation instanceof Model) {
                    $relation->save();
                }
                elseif ($relation instanceof RelationCollection) {
                    $relation->saveAll();
                }
            }
        });
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

        $this->processing(function(Connection $conn) {
            $count = $conn->delete($this->getTable())
                ->where($this->getPrimaryKeyName(), $this->getPrimaryKey())
                ->execute();

            $this->deleted = $count > 0;
        });

        return $this->deleted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param Closure $callback
     */
    protected function processing(Closure $callback)
    {
        try {
            if (!$this->processing) {
                $this->processing = true;
                $callback($this->getConnection());
            }
        }
        finally {
            $this->processing = false;
        }
    }
}
