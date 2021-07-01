<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs;
use function json_decode;
use function json_encode;

class JsonCodec implements CodecInterface
{
    /**
     * @return array
     */
    public function getContentTypes(): array
    {
        return ['application/json'];
    }

    /**
     * @param string $content
     * @return array
     */
    public function receive(string $content): array
    {
        $flags = JSON_THROW_ON_ERROR
               | JSON_INVALID_UTF8_IGNORE;

        return json_decode($content, true, $flags);
    }

    /**
     * @param array $content
     * @return string
     */
    public function send(array $content): string
    {
        $flags = JSON_THROW_ON_ERROR
               | JSON_INVALID_UTF8_IGNORE
               | JSON_UNESCAPED_SLASHES
               | JSON_UNESCAPED_UNICODE;

        return json_encode($content, $flags);
    }
}
