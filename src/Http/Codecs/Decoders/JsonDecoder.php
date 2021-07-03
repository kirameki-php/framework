<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

use function json_decode;

class JsonDecoder implements DecoderInterface
{
    /**
     * @param string $content
     * @return mixed
     */
    public function decode(string $content): mixed
    {
        $flags = JSON_THROW_ON_ERROR
               | JSON_INVALID_UTF8_IGNORE;

        return json_decode($content, true, $flags);
    }
}
