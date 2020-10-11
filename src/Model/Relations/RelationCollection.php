<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\ModelManager;
use Kirameki\Model\Reflection;
use Kirameki\Model\Model;
use Kirameki\Support\Collection;

class RelationCollection extends Collection
{
    protected Reflection $reflection;

    public function getModelClass(): string
    {
        return $this->reflection->class;
    }

    public function getModelReflection()
    {
        /** @var ModelManager $manager */
        $manager = app()->get(ModelManager::class);
        return $manager->reflect($this->getModelClass());
    }

    /**
     * @param array $properties
     * @return Model
     */
    public function newModel(array $properties = []): Model
    {
        return new $this->reflection->class($properties);
    }
}
