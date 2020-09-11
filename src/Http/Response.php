<?php

namespace Kirameki\Http;

class Response
{
    public string $version;

    public int $statusCode;

    public string $statusPhrase;

    public Headers $headers;

    public string $body;

    public function send()
    {
        header("HTTP/{$this->version} {$this->statusCode} {$this->statusPhrase}", true, $this->statusCode);

        foreach ($this->headers->all() as $name => $value) {
            header($name.': '.$value, true, $this->statusCode);
        }

        echo $this->body;
    }
}
