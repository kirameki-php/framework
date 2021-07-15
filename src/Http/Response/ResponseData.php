<?php declare(strict_types=1);

namespace Kirameki\Http\Response;

use JsonSerializable;

abstract class ResponseData implements JsonSerializable
{
    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return null;
    }
}
