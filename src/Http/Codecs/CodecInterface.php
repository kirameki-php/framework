<?php declare(strict_types=1);

namespace Kirameki\Http\Codecs;

interface CodecInterface
{
    public function getContentTypes(): array;

    public function receive(string $content): array;

    public function send(array $content): string;
}
