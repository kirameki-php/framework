<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs;

interface CodecInterface
{
    /**
     * @return array
     */
    public function getContentTypes(): array;

    /**
     * @param string $content
     * @return array
     */
    public function receive(string $content): array;

    /**
     * @param array $content
     * @return string
     */
    public function send(array $content): string;
}
