<?php

namespace Kirameki\Core;

use ArrayAccess;

class Config implements ArrayAccess
{
    protected array $entries = [];

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

    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function all(): array
    {
        return $this->entries;
    }

    public function get(string $key)
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

    public function set(string $key, $value)
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
    public function dig(string $name): static
    {
        return new Config($this->get($name) ?? []);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->entries);
    }

    public function offsetGet($offset)
    {
        return $this->entries[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->entries[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->entries[$offset]);
    }
}