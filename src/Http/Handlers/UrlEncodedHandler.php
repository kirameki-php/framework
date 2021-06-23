<?php declare(strict_types=1);

namespace Kirameki\Http\Handlers;

class UrlEncodedHandler implements HandlerInterface
{
    /**
     * @return array
     */
    public function getContentTypes(): array
    {
        return ['application/x-www-form-urlencoded'];
    }

    /**
     * @param string $content
     * @return array
     */
    public function receive(string $content): array
    {
        $result = [];
        parse_str($content, $result);
        return $result;
    }

    /**
     * @param array $content
     * @return string
     */
    public function send(array $content): string
    {
        return http_build_query($content);
    }
}
