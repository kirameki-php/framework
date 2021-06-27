<?php declare(strict_types=1);

namespace Kirameki\Http;

/**
 * @method processors($registrar): void
 */
abstract class Controller
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param $data
     * @return ResponseData
     */
    public abstract function action($data): ResponseData;
}
