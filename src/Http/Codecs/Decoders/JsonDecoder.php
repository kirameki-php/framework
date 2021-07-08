<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs\Decoders;

use JsonException;
use Kirameki\Http\Exceptions\DecodeException;
use function json_decode;

class JsonDecoder implements DecoderInterface
{
    /**
     * @param string $content
     * @return array
     */
    public function decode(string $content): array
    {
        try {
            return json_decode($content, true, JSON_THROW_ON_ERROR) ?? [];
        }
        catch (JsonException $exception) {
            throw new DecodeException($exception->getMessage(), $content);
        }
    }
}
