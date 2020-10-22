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
     * @var array
     */
    protected array $persistedProperties = [];

    /**
     * @param array $properties
     * @return $this
     */
    protected function setPersistedProperties(array $properties)
    {
        $this->persistedProperties = $properties;
        return $this;
    }

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

            if ($this->isNewRecord()) {
                $properties = $this->getProperties();
                $conn->insertInto($table)->value($properties)->execute();
            }
            else {
                $properties = $this->getPropertiesForUpdate();
                $conn->update($table)->set($properties)->execute();
                // ORDER MATTERS: assign it only after the query has been executed!
                $this->setPersistedProperties($properties);
            }

            $this->clearDirty();

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

    /**
     * @return array
     */
    protected function getPropertiesForUpdate(): array
    {
        $properties = [];
        foreach ($this->getDirtyProperties() as $name => $value) {
            $properties[$name] = $this->getCast($name)->set($this, $name, $value);
        }
        return $properties;
    }
}
