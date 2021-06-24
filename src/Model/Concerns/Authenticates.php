<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Authenticates
{
    /**
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return $this->getPrimaryKeyName();
    }
}
