<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

class StreamDecoder implements Decoder
{
    /**
     * @param string $content
     * @return array
     */
    public function decode(string $content): array
    {
        return ['content' => $content];
    }
}
