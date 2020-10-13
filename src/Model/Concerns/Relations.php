<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;
use Kirameki\Model\Relations\Relation;

/**
 * @mixin Model
 */
trait Relations
{
    /**
     * @param string $name
     * @return bool
     */
    public function isRelation(string $name): bool
    {
        return isset($this->getReflection()->relations[$name]);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getRelation(string $name)
    {
        return $this->resolved[$name] ?? null;
    }

    /**
     * @param string $name
     * @return $this
     */
    protected function loadRelation(string $name)
    {
        $reflection = $this->getReflection();
        $relation = $reflection->relations[$name];
        return $this;
    }

    /**
     * @param string $name
     * @param Relation $relation
     * @return $this
     */
    public function setRelation(string $name, Relation $relation)
    {
        $this->resolved[$name] = $relation;
        return $this;
    }
}
