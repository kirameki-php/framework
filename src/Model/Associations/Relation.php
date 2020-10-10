<?php

namespace Kirameki\Model\Associations;

use Kirameki\Model\Model;

abstract class Relation
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Model
     */
    protected Model $source;

    /**
     * @var Model
     */
    protected Model $reference;

    /**
     * @var string|null
     */
    protected ?string $inverseName;

    /**
     * @var string
     */
    protected string $sourceKey;

    /**
     * @var string
     */
    protected string $referenceKey;

    /**
     * @param string $name
     * @param Model $source
     * @param Model $reference
     * @param string|null $sourceKey
     * @param string|null $referenceKey
     * @param string|null $inverseOf
     */
    public function __construct(string $name, Model $source, Model $reference, ?string $sourceKey = null, ?string $referenceKey = null, ?string $inverseOf = null)
    {
        $this->name = $name;
        $this->source = $source;
        $this->sourceKey = $sourceKey ?? lcfirst(class_basename($reference).'Id');
        $this->reference = $reference;
        $this->referenceKey = $referenceKey ?? $reference->getPrimaryKeyName();
        $this->inverseName = $inverseOf ?? class_basename($reference);
    }
}
