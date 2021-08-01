<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

use function json_decode;

class JsonDecoder implements DecoderInterface
{
    /**
     * @param string $content
     * @return array
     */
    public function decode(string $content): array
    {
        return json_decode($content, true, JSON_THROW_ON_ERROR) ?? [];
    }
}
