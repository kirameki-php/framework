<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;
use Kirameki\Support\Arr;
use Kirameki\Support\Json;

/**
 * @mixin Model
 */
trait Serialization
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->getProperties();
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
