<?php

namespace Kirameki\Http;

use Carbon\Carbon;
use RuntimeException;

/**
 * @property-read Headers $headers
 * @property-read Parameters $parameters
 */
class Request
{
    public string $protocol;

    public string $method;

    public Url $url;

    public ?string $body;

    protected float $timestamp;

    protected ?Headers $_headers;

    protected ?Parameters $_parameters;

    public static function fromServerVars(): static
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $method = $_REQUEST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $url = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $headers = new Headers(getallheaders());
        $body = file_get_contents('php://input');
        $time = $_SERVER['REQUEST_TIME_FLOAT'];

        return new static($protocol, $method, $url, $headers, $body, $time);
    }

    public function __construct(string $protocol, string $method, string $url, ?Headers $headers = null, ?string $body = null, ?float $timestamp = null)
    {
        $this->protocol = $protocol;
        $this->method = strtoupper($method);
        $this->url = new Url(parse_url($url));
        $this->body = $body;
        $this->timestamp = $timestamp ?? microtime(true);
        $this->_headers = $headers ?? new Headers;
        $this->_parameters = null;
    }

    public function httpVersion(): string
    {
        return substr($this->protocol, 5);
    }

    public function time(): Carbon
    {
        return Carbon::createFromTimestampMs($this->timestamp * 1000);
    }

    public function elapsedSeconds(): float
    {
        return microtime(true) - $this->timestamp;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isSecure(): bool
    {
        return $this->url->schema() === 'https';
    }

    public function isAjax(): bool
    {
        return $this->headers->is('X-Requested-With', 'XMLHttpRequest');
    }

    protected function resolveParameters(): Parameters
    {
        $contentType = $this->headers->get('Content-Type');
        return $contentType
            ? Parameters::fromMediaType($contentType, $this->body)
            : Parameters::blank();
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

    public function __get(string $name)
    {
        if ($name === 'headers') {
            return $this->_headers;
        }

        if ($name === 'parameters') {
            return $this->_parameters ??= $this->resolveParameters();
        }

        throw new RuntimeException('Undefined Property: '.$name);
    }
}
