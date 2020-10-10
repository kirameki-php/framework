<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Relations
{
    /**
     * @var Model[]
     */
    protected array $loadedRelations = [];

    public function relationExists(string $name)
    {
        return $this->relationLoaded($name) || method_exists($this, $this->relationMethodFor($name));
    }

    public function relationLoaded(string $name)
    {
        return array_key_exists($name, $this->loadedRelations);
    }

    public function getLoadedRelations()
    {
        return $this->loadedRelations;
    }

    public function getRelation(string $name)
    {
        if ($this->relationLoaded($name)) {
            return $this->loadedRelations[$name];
        }
        $relationMethod = $this->relationMethodFor($name);
        return new $relationMethod();
    }

    public function setRelation(string $name, $related)
    {
        $this->loadedRelations[$name] = $related;
        return $this;
    }

    protected function relationMethodFor(string $name)
    {
        return $name.'Relation';
    }
}
