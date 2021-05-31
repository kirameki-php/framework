<?php

namespace Kirameki\Core;

use ArrayAccess;

class Config implements ArrayAccess
{
    /**
     * @var array
     */
    protected array $entries = [];

    /**
     * @param string $dir
     * @return static
     */
    public static function fromDirectory(string $dir): static
    {
        $entries = [];
        foreach (scandir($dir) as $file) {
            if (str_ends_with($file, '.php')) {
                $entries[substr(basename($file), 0, -4)] = require $dir.'/'.$file;
            }
        }
        return new static($entries);
    }

    /**
     * @param array $entries
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->entries[$key] ?? null;
        }

        $curr = &$this->entries;
        foreach (explode('.', $key) as $segment) {
            if (!isset($curr[$segment])) {
                return null;
            }
            $curr = &$curr[$segment];
        }

        return $curr;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value)
    {
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $curr = &$this->entries;
        foreach ($segments as $segment) {
            $curr[$segment] ??= [];
            $curr = &$curr[$segment];
        }
        $curr[$lastSegment] = $value;
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key)
    {
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $curr = &$this->entries;
        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $curr)) {
                return;
            }
            $curr = &$curr[$segment];
        }
        unset($curr[$lastSegment]);
    }

    /**
     * @param string $name
     * @return static
     */
    public function extract(string $name): static
    {
        return new Config($this->get($name) ?? []);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->entries);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->entries[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->entries[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->entries[$offset]);
    }
}