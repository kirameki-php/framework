<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Encoders;

use function json_encode;

class JsonEncoder implements EncoderInterface
{
    /**
     * @param array $content
     * @return string
     */
    public function encode(mixed $content): string
    {
        $flags = JSON_THROW_ON_ERROR
            | JSON_INVALID_UTF8_IGNORE
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE;

        return json_encode($content, $flags);
    }
}
