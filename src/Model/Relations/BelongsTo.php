<?php

namespace Kirameki\Model\Relations;

class BelongsTo extends Relation
{
    /**
     * @return string
     */
    public function getSrcKey(): string
    {
        return $this->srcKey ??= lcfirst(class_basename($this->getDest()->class)).'Id';
    }

    /**
     * @return string
     */
    public function getDestKey(): string
    {
        return $this->destKey ??= $this->getDest()->primaryKey;
    }

    /**
     * @return bool
     */
    public function returnsMany(): bool
    {
        return false;
    }
}
