<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Model\Model;
use Kirameki\Model\Relations\RelationCollection;
use RuntimeException;
use function sprintf;

/**
 * @mixin Model
 */
trait Persistence
{
    /**
     * @var bool
     */
    public bool $_persisted = false;

    /**
     * @var bool
     */
    public bool $_deleted = false;

    /**
     * @var bool
     */
    protected bool $_processing = false;

    /**
     * @return $this
     */
    public function save(): static
    {
        if ($this->isDeleted()) {
            throw new RuntimeException(sprintf('Trying to save record which was deleted! (%s:%s)',
                $this->getTable(),
                $this->getPrimaryKey())
            );
        }

        $this->processing(function(Connection $conn) {
            $table = $this->getTable();

            $this->isNewRecord()
                ? $conn->insertInto($table)->value($this->getPropertiesForInsert())->execute()
                : $conn->update($table)->set($this->getPropertiesForUpdate())->execute();

            $this->setDirtyPropertiesAsPersisted();
            $this->clearDirtyProperties();

            foreach ($this->getRelations() as $relation) {
                $relation->save();
            }
        });

        return $this;
    }

    /**
     * @return bool
     */
    public function isNewRecord(): bool
    {
        return !$this->_persisted && !$this->_deleted;
    }

    /**
     * @return bool
     */
    public function isPersisted(): bool
    {
        return $this->_persisted && !$this->_deleted;
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        // trying to delete a record with dirty primary key is dangerous.
        if ($this->isDirty($this->getPrimaryKeyName())) {
            throw new RuntimeException('Deleting a record with dirty primary key is not allowed.'); // TODO Better exception handling
        }

        $this->processing(function(Connection $conn) {
            $count = $conn->delete($this->getTable())
                ->where($this->getPrimaryKeyName(), $this->getPrimaryKey())
                ->execute();

            $this->_deleted = $count > 0;
        });

        return $this->_deleted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->_deleted;
    }

    /**
     * @param Closure $callback
     */
    protected function processing(Closure $callback): void
    {
        try {
            if (!$this->_processing) {
                $this->_processing = true;
                $callback($this->getConnection());
            }
        }
        finally {
            $this->_processing = false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPropertiesForInsert(): array
    {
        return $this->getProperties();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPropertiesForUpdate(): array
    {
        return $this->getDirtyProperties();
    }
}
