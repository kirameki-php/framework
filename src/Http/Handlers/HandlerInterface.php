<?php


namespace Kirameki\Http\Handlers;

interface HandlerInterface
{
    public function getContentTypes(): array;

    public function receive(string $content): array;

    public function send(array $content): string;
}
