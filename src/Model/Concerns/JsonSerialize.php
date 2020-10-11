<?php

namespace Kirameki\Model\Concerns;

use Carbon\Carbon;
use Kirameki\Model\Model;
use Kirameki\Support\Json;

/**
 * @mixin Model
 */
trait JsonSerialize
{
    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return Json::encode($this, $options);
    }
}
