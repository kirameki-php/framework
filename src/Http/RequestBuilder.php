<?php

namespace Kirameki\Http;

use Carbon\Carbon;
use Kirameki\Application;

class RequestBuilder
{
    public static function build()
    {
        $components = parse_url($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $components['schema'] = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'] ?? 'http';
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $components['user'] = $_SERVER['PHP_AUTH_USER'];
        }
        if (isset($_SERVER['PHP_AUTH_PW'])) {
            $components['pass'] = $_SERVER['PHP_AUTH_PW'];
        }

        $url = new Url($components);
        $headers = new Headers(getallheaders());
        $parameters = new Parameters();
        $body = file_get_contents('php://input');

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $method = $parameters->get('_method') ?? $_SERVER['REQUEST_METHOD'];

        return new Request($protocol, $method, $url, $headers, $parameters, $body);
    }
}
