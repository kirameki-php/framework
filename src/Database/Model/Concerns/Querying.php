<?php

namespace Kirameki\Database\Model\Concerns;

use Kirameki\Database\Model\Model;
use Kirameki\Database\Query\Builders\StatementBuilder as BaseBuilder;

/**
 * @mixin Model
 */
trait Querying
{
    protected ?string $connection;
    protected ?string $table;
    protected ?string $primaryKey;
    protected ?bool $autoIncrement;

    public bool $exists;

    protected bool $processing = false;

    public function getConnection(): string
    {
        return $this->connection ?? config()->get('database.default');
    }

    public function setConnection(string $name)
    {
        $this->connection = $name;
        return $this;
    }

    public function getTable(): string
    {
        return $this->table ?? class_basename($this);
    }

    public function getPrimaryKeyName(): string
    {
        return $this->primaryKey ?? 'id';
    }

    public function getPrimaryKey()
    {
        return $this->getAttribute($this->getPrimaryKeyName());
    }

    public function isAutoIncrementing(): bool
    {
        return $this->autoIncrement ?? true;
    }

    public function newQuery()
    {
        $connection = app('db')->connection($this->getConnection());
        return new Builder($this, $connection);
    }

    public function save()
    {
        return $this->process(function () {
            $this->triggerEvent('saving');

            $this->exists
                ? $this->performUpdate()
                : $this->performInsert();

            foreach ($this->loadedRelations as $relation) {
                $relation->save();
            }

            $this->triggerEvent('saved');
        });
    }

    public function destroy()
    {
        return $this->process(function() {
            $this->performDelete();
        });
    }

    protected function process(callable $callable)
    {
        if ($this->processing) {
            return $this;
        }

        $this->processing = true;

        $callable();

        $this->processing = false;

        return $this;
    }

    protected function performInsert()
    {
        $this->triggerEvent('creating');
        $this->newQuery()->insert($this->getAttributes());
        $this->triggerEvent('created');
    }

    protected function performUpdate()
    {
        $this->triggerEvent('updating');
        $this->newQuery()->update($this->changedAttributes());
        $this->triggerEvent('updated');
    }

    protected function performDelete()
    {
        $this->triggerEvent('deleting');
        $this->newQuery()->where($this->getPrimaryKeyName(), $this->getPrimaryKey())->delete();
        $this->triggerEvent('deleted');
    }
}
