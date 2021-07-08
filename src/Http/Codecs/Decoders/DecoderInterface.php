<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

interface DecoderInterface
{
    /**
     * @param string $content
     * @return array
     */
    public function decode(string $content): array;
}
