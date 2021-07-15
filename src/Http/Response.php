<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Response\ResponseHeaders;
use Stringable;
use function header;
use function headers_sent;
use function ini_get;
use function ksort;
use function strlen;
use function gmdate;

class Response implements Stringable
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
     * @var ResponseHeaders
     */
    public ResponseHeaders $headers;

    /**
     * @var string
     */
    public string $body;


    /**
     * @param string $body
     * @param int $code
     * @param ResponseHeaders|null $headers
     * @param string $version
     */
    public function __construct(string $body = '', int $code = StatusCode::OK, ResponseHeaders $headers = null, string $version = '1.1')
    {
        $this->version = $version;
        $this->statusCode = $code;
        $this->statusPhrase = StatusCode::asPhrase($code);
        $this->headers = $headers ?? new ResponseHeaders();
        $this->body = $body;
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
        if (!headers_sent()) {
            if (!$this->headers->has('Content-Length')) {
                $this->headers->set('Content-Length', (string) strlen($this->body));
            }

            header($this->statusLine(), true, $this->statusCode);

            foreach ($this->headers->all() as $name => $values) {
                foreach ($values as $value) {
                    header($name.': '.$value, false, $this->statusCode);
                }
            }
        }

        echo $this->body;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $headers = clone $this->headers;

        $headers->set('Date', gmdate('D, d M Y H:i:s T'));

        if (!$headers->has('Content-Length')) {
            $headers->set('Content-Length', (string) strlen($this->body));
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
