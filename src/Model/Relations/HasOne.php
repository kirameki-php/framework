<?php

namespace Kirameki\Model\Relations;

class HasOne extends OneToOne
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= $this->getSrc()->primaryKey;
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= lcfirst(class_basename($this->getSrc()->class)).'Id';
    }
}
