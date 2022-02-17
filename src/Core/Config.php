<?php declare(strict_types=1);

namespace Kirameki\Core;

use ArrayAccess;
use RuntimeException;

/**
 * @template-implements ArrayAccess<array-key, mixed>
 */
class Config implements ArrayAccess
{
    /**
     * @var array<array-key, mixed>
     */
    protected array $entries;

    /**
     * @param string $dir
     * @return static
     */
    public static function fromDirectory(string $dir): static
    {
        $entries = [];
        $files = scandir($dir);

        if ($files === false) {
            throw new RuntimeException($dir.' is not a directory');
        }

        foreach ($files as $file) {
            if (str_ends_with($file, '.php')) {
                $entries[substr(basename($file), 0, -4)] = require $dir.'/'.$file;
            }
        }

        return new static($entries);
    }

    /**
     * @param array<array-key, mixed> $entries
     */
    public function __construct(array &$entries)
    {
        $this->entries = &$entries;
    }

    /**
     * @return array<array-key, mixed>
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
        return $this->getInternal($key, false);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getOrFail(string $key): mixed
    {
        return $this->getInternal($key, true);
    }

    /**
     * @param non-empty-string $key
     * @return string
     */
    public function getString(string $key): string
    {
        return $this->getOrFail($key); /** @phpstan-ignore-line */
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getStringOrNull(string $key): string|null
    {
        return $this->get($key); /** @phpstan-ignore-line */
    }

    /**
     * @param string $key
     * @param bool $strict
     * @return mixed
     */
    protected function getInternal(string $key, bool $strict): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->entries[$key] ?? null;
        }

        $curr = &$this->entries;
        foreach (explode('.', $key) as $segment) {
            if (!isset($curr[$segment])) {
                if ($strict) {
                     throw new RuntimeException("$segment does not exist. (Key: $key)");
                }
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
    public function set(string $key, mixed $value): void
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
    public function delete(string $key): void
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
    public function for(string $name): static
    {
        $ptr = &$this->entries;
        foreach (explode('.', $name) as $segment) {
            $ptr = &$ptr[$segment];
        }
        return new static($ptr);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->entries);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->entries[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->entries[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->entries[$offset]);
    }
}