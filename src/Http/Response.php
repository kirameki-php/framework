<?php declare(strict_types=1);

namespace Kirameki\Http;

use Stringable;

class Response implements Stringable
{
    const CRLF = "\r\n";

    public Request $request;

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
     * @param Request $request
     * @param int $code
     * @param Headers|null $headers
     * @param string $body
     */
    public function __construct(Request $request, int $code = 200, Headers $headers = null, string $body = '')
    {
        $this->request = $request;
        $this->version = $request->httpVersion();
        $this->statusCode = $code;
        $this->statusPhrase = static::codeToPhrase($code);
        $this->headers = $headers ?? new Headers();
        $this->body = $body;
    }

    /**
     * @param int $code
     * @return string
     */
    public static function codeToPhrase(int $code): string
    {
        return match ($code) {
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choice',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Too Early',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        };
    }

    /**
     * @return void
     */
    public function send()
    {
        if (!headers_sent()) {
            header("HTTP/$this->version $this->statusCode $this->statusPhrase", true, $this->statusCode);

            foreach ($this->headers->all() as $name => $value) {
                header($name.': '.$value, true, $this->statusCode);
            }

            header('Content-Length: '.strlen($this->body), true, $this->statusCode);
        }

        echo $this->body;
    }

    /**
     * @return string
     */
    public function statusLine(): string
    {
        return "HTTP/$this->version $this->statusCode $this->statusPhrase";
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $str = "HTTP/$this->version $this->statusCode $this->statusPhrase".self::CRLF;

        $headers = $this->headers->all();
        $headers['Content-Length'] = strlen($this->body);
        $headers['Date'] = gmdate('D, d M Y H:i:s T');
        if (ini_get('expose_php') === '1') {
            $headers['X-Powered-By'] = 'PHP/'.PHP_VERSION;
        }
        ksort($headers);

        foreach ($headers as $name => $value) {
            $str.= $name.': '.$value.self::CRLF;
        }

        $str.= self::CRLF;

        $str.= $this->body;

        return $str;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
