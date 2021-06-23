<?php declare(strict_types=1);

namespace Kirameki\Http;

class Response
{
    /**
     * @var string
     */
    public string $version;

    /**
     * @var int
     */
    public int $statusCode;

    /**
     * @var string
     */
    public string $statusPhrase;

    /**
     * @var Headers
     */
    public Headers $headers;

    /**
     * @var string
     */
    public string $body;

    /**
     * @return void
     */
    public function send()
    {
        header("HTTP/{$this->version} {$this->statusCode} {$this->statusPhrase}", true, $this->statusCode);

        foreach ($this->headers->all() as $name => $value) {
            header($name.': '.$value, true, $this->statusCode);
        }

        echo $this->body;
    }
}
