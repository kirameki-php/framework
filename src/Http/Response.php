<?php declare(strict_types=1);

namespace Kirameki\Http;

use Stringable;
use function header;
use function headers_sent;
use function ini_get;
use function ksort;
use function strlen;
use function gmdate;

class Response implements Stringable
{
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
     * @var ResponseHeaders
     */
    public ResponseHeaders $headers;

    /**
     * @var ResponseData
     */
    public ResponseData $data;


    /**
     * @param Request $request
     * @param ResponseData $data
     * @param int $code
     * @param ResponseHeaders|null $headers
     */
    public function __construct(Request $request, ResponseData $data, int $code = 200, ResponseHeaders $headers = null)
    {
        $this->request = $request;
        $this->version = $request->httpVersion();
        $this->statusCode = $code;
        $this->statusPhrase = static::codeToPhrase($code);
        $this->headers = $headers ?? new ResponseHeaders();
        $this->data = $data;
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
     * @return string
     */
    public function statusLine(): string
    {
        return "HTTP/$this->version $this->statusCode $this->statusPhrase";
    }

    /**
     * @return void
     */
    public function send()
    {
        $body = $this->data->toString();

        if (!headers_sent()) {
            if (!$this->headers->has('Content-Length')) {
                $this->headers->set('Content-Length', (string) strlen($body));
            }

            header($this->statusLine(), true, $this->statusCode);

            foreach ($this->headers->all() as $name => $values) {
                foreach ($values as $value) {
                    header($name.': '.$value, false, $this->statusCode);
                }
            }
        }

        echo $body;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $body = $this->data->toString();

        $headers = clone $this->headers;

        $headers->set('Date', gmdate('D, d M Y H:i:s T'));

        if (!$headers->has('Content-Length')) {
            $headers->set('Content-Length', (string) strlen($body));
        }

        if (ini_get('expose_php') === '1') {
            $headers->set('X-Powered-By', 'PHP/'.PHP_VERSION);
        }

        $headersArray = $headers->all();
        ksort($headersArray);

        $str = $this->statusLine().Request::CRLF;
        foreach ($headersArray as $name => $values) {
            foreach ($values as $value) {
                $str.= $name.': '.$value.Request::CRLF;
            }
        }
        $str.= Request::CRLF;
        $str.= $this->data->toString();

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
