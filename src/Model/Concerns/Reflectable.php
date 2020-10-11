<?php

namespace Kirameki\Model\Concerns;

use Carbon\Carbon;
use Kirameki\Model\Reflection;
use Kirameki\Model\Model;
use Kirameki\Support\Json;

/**
 * @mixin Model
 */
trait Reflectable
{
    /**
     * @var Reflection
     */
    protected static Reflection $reflection;

    /**
     * @param Reflection $reflection
     */
    abstract public function define(Reflection $reflection): void;
}
