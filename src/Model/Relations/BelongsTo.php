<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @template-extends OneToOne<TSrc, TDest>
 */
class BelongsTo extends OneToOne
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= $this->guessSrcKeyName();
    }

    /**
     * @return string
     */
    protected function guessSrcKeyName(): string
    {
        return lcfirst(class_basename($this->getDestReflection()->class)).'Id';
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= $this->getDestReflection()->primaryKey;
    }
}
