<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Encoders;

interface Encoder
{
    /**
     * @param array $content
     * @return string
     */
    public function encode(mixed $content): string;
}
