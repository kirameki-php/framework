<?php

namespace Kirameki\Http;

use Carbon\Carbon;
use Kirameki\Core\Application;
use RuntimeException;

class Url
{
    protected array $components = [];

    public function __construct(array $components)
    {
        $this->components = $components;
        if (!isset($this->components['host'])) {
            throw new RuntimeException('Url components must include "host".');
        }
    }

    public function schema(): string
    {
        return $this->components['schema'] ?? 'http';
    }

    public function userinfo(): ?string
    {
        $userinfo = $this->username();
        if ($userinfo !== null && $this->password() !== null) {
            $userinfo.= ':'.$this->password();
        }
        return $userinfo;
    }

    public function username(): ?string
    {
        return $this->components['user'] ?? null;
    }

    public function password(): ?string
    {
        return $this->components['pass'] ?? null;
    }

    public function host(): string
    {
        return $this->components['host'];
    }

    public function port(): ?int
    {
        return $this->components['port'] ?? null;
    }

    public function authority(): string
    {
        $host = $this->host();
        $port = $this->port();
        $authority = $userinfo = $this->userinfo();
        $authority.= ($userinfo !== null) ? '@'.$host : $host;
        if ($port !== null && $host !== null) {
            $authority.= ':'.$port;
        }
        return $authority;
    }

    public function path(): ?string
    {
        return $this->components['path'] ?? null;
    }

    public function query(): ?string
    {
        return $this->components['query'] ?? null;
    }

    public function pathAndQuery(): ?string
    {
        $str = null;
        $path = $this->path();
        if ($path !== null) {
            $str.= $path;
        }
        $query = $this->query();
        if ($query !== null) {
            $str.= '?'.$query;
        }
        return $str;
    }

    public function fragment(): ?string
    {
        return $this->components['fragment'] ?? null;
    }

    public function isDefaultPort(): bool
    {
        $schema = $this->schema();
        $port = $this->port();
        if ($port === null) return true;
        if ('http'  === $schema &&  80 === $port) return true;
        if ('https' === $schema && 443 === $port) return true;
        return false;
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        $str = $this->schema();
        $str.= '://'.$this->authority();
        $path = $this->path();
        if ($path !== null) {
            $str.= $path;
        }
        $query = $this->query();
        if ($query !== null) {
            $str.= '?'.$query;
        }
        $fragment = $this->fragment();
        if ($fragment !== null) {
            $str.= '#'.$fragment;
        }
        return $str;
    }
}
