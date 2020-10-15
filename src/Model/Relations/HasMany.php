<?php

namespace Kirameki\Model\Relations;

class HasMany extends Relation
{
    /**
     * @return string
     */
    public function getSrcKey(): string
    {
        return $this->srcKey ??= $this->getSrc()->primaryKey;
    }

    /**
     * @return string
     */
    public function getDestKey(): string
    {
        return $this->destKey ??= lcfirst(class_basename($this->getSrc()->class)).'Id';
    }

    /**
     * @return bool
     */
    public function returnsMany(): bool
    {
        return true;
    }
}
