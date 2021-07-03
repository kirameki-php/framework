<?php declare(strict_types=1);

namespace Kirameki\Http;

use RuntimeException;

class Url
{
    /**
     * @var array
     */
    protected array $components = [];

    /**
     * @param array $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
        if (!isset($this->components['host'])) {
            throw new RuntimeException('Url components must include "host".');
        }
    }

    /**
     * @return string
     */
    public function schema(): string
    {
        return $this->components['scheme'] ?? 'http';
    }

    /**
     * @return string|null
     */
    public function userinfo(): ?string
    {
        $userinfo = $this->username();
        if ($userinfo !== null && $this->password() !== null) {
            $userinfo.= ':'.$this->password();
        }
        return $userinfo;
    }

    /**
     * @return string|null
     */
    public function username(): ?string
    {
        return $this->components['user'] ?? null;
    }

    /**
     * @return string|null
     */
    public function password(): ?string
    {
        return $this->components['pass'] ?? null;
    }

    /**
     * @return string
     */
    public function host(): string
    {
        return $this->components['host'];
    }

    /**
     * @return int|null
     */
    public function port(): ?int
    {
        return $this->components['port'] ?? null;
    }

    /**
     * @return string
     */
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

    /**
     * @return string|null
     */
    public function path(): ?string
    {
        return $this->components['path'] ?? null;
    }

    /**
     * @return string|null
     */
    public function query(): ?string
    {
        return $this->components['query'] ?? null;
    }

    /**
     * @return array
     */
    public function queryParameters(): array
    {
        $result = [];
        parse_str($this->query() ?? '', $result);
        return $result;
    }

    /**
     * @return string|null
     */
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

    /**
     * @return string|null
     */
    public function fragment(): ?string
    {
        return $this->components['fragment'] ?? null;
    }

    /**
     * @return bool
     */
    public function isDefaultPort(): bool
    {
        $schema = $this->schema();
        $port = $this->port();
        if ($port === null) return true;
        if ('http'  === $schema &&  80 === $port) return true;
        if ('https' === $schema && 443 === $port) return true;
        return false;
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
