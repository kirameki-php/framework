<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;
use Kirameki\Support\Json;

/**
 * @mixin Model
 */
trait Serialization
{
    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toAssoc();
    }

    /**
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return Json::encode($this, $options);
    }
}
