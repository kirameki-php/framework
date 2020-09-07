<?php

namespace Kirameki\Http;

use Carbon\Carbon;

class Request
{
    public string $protocol;

    public string $method;

    public Url $url;

    public Headers $headers;

    public Parameters $parameters;

    protected ?string $body;

    public function __construct(string $protocol, string $method, Url $url, Headers $headers, Parameters $parameters, ?string $body = null)
    {
        $this->protocol = $protocol;
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $headers ?? new Headers();
        $this->parameters = $parameters ?? new Parameters();
        $this->body = $body;
    }

    public function httpVersion()
    {
        return substr($this->protocol, 5);
    }

    public function time(): Carbon
    {
        $timestampMs = $_SERVER['REQUEST_TIME_FLOAT'] * 1000;
        return Carbon::createFromTimestampMs($timestampMs);
    }

    public function elapsedSeconds(): float
    {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function originalMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isSecure(): bool
    {
        return $this->url->schema() === 'https';
    }

    public function isAjax(): bool
    {
        return $this->headers->is('X-Requested-With', 'XMLHttpRequest');
    }

    public function hasBody(): bool
    {
        return $this->body !== null;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        $newline = "\r\n";
        $raw = sprintf('%s %s %s', $this->method, $this->url, $this->protocol).$newline;
        if ($headers = $this->headers->toString()) {
            $raw.= $headers.$newline;
        }
        $raw.= $newline;
        if ($this->body !== null) {
            $raw.= $this->body.$newline;
        }
        return $raw;
    }

    public function __clone()
    {
        $this->url = clone $this->url;
        $this->headers = clone $this->headers;
        $this->parameters = clone $this->parameters;
    }
}
