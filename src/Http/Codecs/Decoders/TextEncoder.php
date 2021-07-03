<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

use Kirameki\Http\Codecs\Encoders\EncoderInterface;

class TextEncoder implements EncoderInterface
{
    /**
     * @param array $content
     * @return string
     */
    public function encode(mixed $content): string
    {
        return $content;
    }
}
