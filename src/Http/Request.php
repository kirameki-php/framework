<?php declare(strict_types=1);

namespace Kirameki\Http;

use Carbon\Carbon;
use RuntimeException;
use Stringable;

/**
 * @property-read ResponseHeaders $headers
 * @property-read Parameters $parameters
 */
class Request implements Stringable
{
    const CRLF = "\r\n";

    /**
     * @var string
     */
    public string $protocol;

    /**
     * @var string
     */
    public string $method;

    /**
     * @var Url
     */
    public Url $url;

    /**
     * @var string|null
     */
    public ?string $body;

    /**
     * @var float
     */
    protected float $timestamp;

    /**
     * @var ResponseHeaders|null
     */
    protected ?ResponseHeaders $_headers;

    /**
     * @var Parameters|null
     */
    protected ?Parameters $_parameters;

    /**
     * @return static
     */
    public static function fromServerVars(): static
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $method = $_REQUEST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $url = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $headers = new ResponseHeaders(getallheaders());
        $body = file_get_contents('php://input');
        $time = $_SERVER['REQUEST_TIME_FLOAT'];

        return new static($protocol, $method, $url, $headers, $body, $time);
    }

    /**
     * @param string $protocol
     * @param string $method
     * @param string $url
     * @param ResponseHeaders|null $headers
     * @param string|null $body
     * @param float|null $timestamp
     */
    public function __construct(string $protocol, string $method, string $url, ?ResponseHeaders $headers = null, ?string $body = null, ?float $timestamp = null)
    {
        $this->protocol = $protocol;
        $this->method = strtoupper($method);
        $this->url = new Url(parse_url($url));
        $this->body = $body;
        $this->timestamp = $timestamp ?? microtime(true);
        $this->_headers = $headers ?? new ResponseHeaders;
        $this->_parameters = null;
    }

    /**
     * @return string
     */
    public function httpVersion(): string
    {
        return substr($this->protocol, 5);
    }

    /**
     * @return Carbon
     */
    public function time(): Carbon
    {
        return Carbon::createFromTimestampMs($this->timestamp * 1000);
    }

    /**
     * @return float
     */
    public function elapsedSeconds(): float
    {
        return microtime(true) - $this->timestamp;
    }

    /**
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method === Method::GET;
    }

    /**
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->method === Method::HEAD;
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method === Method::POST;
    }

    /**
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method === Method::PUT;
    }

    /**
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method === Method::DELETE;
    }

    /**
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->method === Method::PATCH;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->url->schema() === 'https';
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->headers->matches('X-Requested-With', 'XMLHttpRequest');
    }

    /**
     * @return Parameters
     */
    protected function resolveParameters(): Parameters
    {
        $contentType = $this->headers->getFirst('Content-Type');
        return $contentType
            ? Parameters::fromMediaType($contentType, $this->body)
            : Parameters::blank();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $raw = sprintf('%s %s %s', $this->method, $this->url, $this->protocol).self::CRLF;
        if ($headers = $this->headers->toString()) {
            $raw.= $headers.self::CRLF;
        }
        $raw.= self::CRLF;
        if ($this->body !== null) {
            $raw.= $this->body.self::CRLF;
        }
        return $raw;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->url = clone $this->url;
        $this->headers = clone $this->headers;
        $this->parameters = clone $this->parameters;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return match ($name) {
            'headers' => $this->_headers,
            'parameters' => $this->_parameters,
            default => throw new RuntimeException('Undefined Property: '.$name),
        };
    }
}
