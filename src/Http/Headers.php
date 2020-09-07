<?php

namespace Kirameki\Http;

use Countable;

class Headers implements Countable
{
    protected array $entries;

    public function __construct(array $entries = [])
    {
        $this->entries = [];
        $this->merge($entries);
    }

    public function all(): array
    {
        return $this->caseSensitiveEntries();
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function is(string $name, string $expectedValue): bool
    {
        return $this->get($name) === $expectedValue;
    }

    public function get(string $name): string|null
    {
        return $this->entries[strtoupper($name)]['value'] ?? null;
    }

    public function set(string $name, string $value): void
    {
        $this->entries[strtoupper($name)] = compact('name', 'value');
    }

    public function merge(array $entries): void
    {
        foreach ($entries as $name => $entry) {
            $this->set($name, $entry);
        }
    }

    public function delete(string $name): void
    {
        unset($this->entries[strtoupper($name)]);
    }

    public function count(): int
    {
        return count($this->entries);
    }

    protected function caseSensitiveEntries(): array
    {
        $entries = [];
        foreach ($this->entries as $data) {
            $entries[$data['name']] = $data['value'];
        }
        return $entries;
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        $raw = '';
        foreach ($this->entries as $data) {
            $raw.= $data['name'].': '.$data['value'];
        }
        return $raw;
    }
}
