<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

class NullDecoder implements DecoderInterface
{
    /**
     * @param string $content
     * @return mixed
     */
    public function decode(string $content): mixed
    {
        return null;
    }
}
