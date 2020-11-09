<?php

namespace Kirameki\Model\Relations;

class BelongsTo extends OneToOne
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= lcfirst(class_basename($this->getDest()->class)).'Id';
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= $this->getDest()->primaryKey;
    }
}
