<?php

namespace Kirameki\Http\Request;

class Request
{
    public Headers $headers;

    public function __construct(Headers $headers = null)
    {
        $this->headers = $headers ?? new Headers();
    }
}
