<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Reflection;
use Kirameki\Model\ModelManager;

abstract class Relation
{
    /**
     * @var ModelManager
     */
    protected ModelManager $manager;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Reflection
     */
    protected Reflection $src;

    /**
     * @var Reflection|null
     */
    protected ?Reflection $dest;

    /**
     * @var string
     */
    protected string $destClass;

    /**
     * @var ?string
     */
    protected ?string $srcKey;

    /**
     * @var string|null
     */
    protected ?string $destKey;

    /**
     * @var string|null
     */
    protected ?string $inverse;

    /**
     * @param ModelManager $manager
     * @param string $name
     * @param Reflection $src
     * @param string $destClass
     * @param string|null $srcKey
     * @param string|null $refKey
     * @param string|null $inverse
     */
    public function __construct(ModelManager $manager, string $name, Reflection $src, string $destClass, ?string $srcKey = null, ?string $refKey = null, ?string $inverse = null)
    {
        $this->manager = $manager;
        $this->name = $name;
        $this->src = $src;
        $this->srcKey = $srcKey;
        $this->dest = null;
        $this->destClass = $destClass;
        $this->destKey = $refKey;
        $this->inverse = $inverse;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Reflection
     */
    public function getSrc(): Reflection
    {
        return $this->src;
    }

    /**
     * @return string
     */
    abstract public function getSrcKey(): string;

    /**
     * @return Reflection
     */
    public function getDest(): Reflection
    {
        return $this->dest ??= $this->manager->reflect($this->destClass);
    }

    /**
     * @return string
     */
    abstract public function getDestKey(): string;
}
