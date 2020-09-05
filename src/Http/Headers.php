<?php

namespace Kirameki\Http\Request;

class Headers
{
    protected array $entries;

    public function __construct(array $entries = [])
    {
        $this->entries = [];
        $this->merge($entries);
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->caseSensitiveEntries());
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function get(string $name): string|null
    {
        $key = strtoupper($name);
        return $this->entries[$key]['value']
            ?? $_POST[$key]
            ?? $_GET[$key]
            ?? null;
    }

    public function set(string $name, string $value): void
    {
        $this->entries[strtoupper($name)] = compact('name', 'value');
    }

    public function merge(array $entries)
    {
        foreach ($entries as $name => $entry) {
            $this->set($name, $entry);
        }
        return $this;
    }

    public function delete(string $name): void
    {
        unset($this->entries[strtoupper($name)]);
    }

    public function count(): int
    {
        return count($_GET) + count($_POST) + count($this->entries);
    }

    protected function caseSensitiveEntries(): array
    {
        $entries = [];
        foreach ($this->entries as $data) {
            $entries[$data['name']] = $data['value'];
        }
        return $entries;
    }
}
