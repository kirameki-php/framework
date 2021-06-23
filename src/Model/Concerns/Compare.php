<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Compare
{
    /**
     * @param Model $model
     * @return bool
     */
    public function is(Model $model): bool
    {
        return $model instanceof $this && $this->getPrimaryKey() === $model->getPrimaryKey();
    }

    /**
     * @param Model $model
     * @return bool
     */
    public function isNot(Model $model): bool
    {
        return !$this->is($model);
    }
}
