<?php declare(strict_types=1);

namespace Kirameki\Http\Handlers;

class MsgpackHandler implements HandlerInterface
{
    /**
     * @return array
     */
    public function getContentTypes(): array
    {
        return ['application/msgpack'];
    }

    /**
     * @param string $content
     * @return array
     */
    public function receive(string $content): array
    {
        return msgpack_unpack($content);
    }

    /**
     * @param array $content
     * @return string
     */
    public function send(array $content): string
    {
        return msgpack_pack($content);
    }
}
