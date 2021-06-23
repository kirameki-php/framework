<?php declare(strict_types=1);

namespace Kirameki\Http;

use Countable;

class Headers implements Countable
{
    /**
     * @var array
     */
    protected array $entries;

    /**
     * @param array $entries
     */
    public function __construct(array $entries = [])
    {
        $this->entries = [];
        $this->merge($entries);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->caseSensitiveEntries();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * @param string $name
     * @param string $expectedValue
     * @return bool
     */
    public function is(string $name, string $expectedValue): bool
    {
        return $this->get($name) === $expectedValue;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function get(string $name): ?string
    {
        return $this->entries[strtoupper($name)]['value'] ?? null;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function set(string $name, string $value): void
    {
        $this->entries[strtoupper($name)] = compact('name', 'value');
    }

    /**
     * @param array $entries
     * @return void
     */
    public function merge(array $entries): void
    {
        foreach ($entries as $name => $entry) {
            $this->set($name, $entry);
        }
    }

    /**
     * @param string $name
     * @return void
     */
    public function remove(string $name): void
    {
        unset($this->entries[strtoupper($name)]);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * @return array
     */
    protected function caseSensitiveEntries(): array
    {
        $entries = [];
        foreach ($this->entries as $data) {
            $entries[$data['name']] = $data['value'];
        }
        return $entries;
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
        $raw = '';
        foreach ($this->entries as $data) {
            $raw.= $data['name'].': '.$data['value'];
        }
        return $raw;
    }
}
