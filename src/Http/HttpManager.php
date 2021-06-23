<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Handlers\HandlerInterface;

class HttpManager
{
    /**
     * @var HandlerInterface[]
     */
    protected array $contentHandlers = [];

    /**
     * @param HandlerInterface $handler
     * @return void
     */
    public function addContentHandler(HandlerInterface $handler)
    {
        foreach ($handler->getContentTypes() as $type) {
            $this->contentHandlers[$type] = $handler;
        }
    }

    /**
     * @param string $contentType
     * @return HandlerInterface|null
     */
    public function getContentHandler(string $contentType): ?HandlerInterface
    {
        return $this->contentHandlers[$contentType] ?? null;
    }
}
