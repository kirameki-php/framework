<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @template-extends OneToOne<TSrc, TDest>
 */
class HasOne extends OneToOne
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= $this->getSrcReflection()->primaryKey;
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= $this->guessDestKeyName();
    }

    /**
     * @return string
     */
    public function guessDestKeyName(): string
    {
        return lcfirst(class_basename($this->getSrcReflection()->class)).'Id';
    }

}
