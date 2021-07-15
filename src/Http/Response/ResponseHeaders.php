<?php declare(strict_types=1);

namespace Kirameki\Http\Response;

use Kirameki\Http\Request;
use Stringable;
use function array_merge;
use function compact;
use function strtoupper;

class ResponseHeaders implements Stringable
{
    /**
     * @var array<string, string[]>
     */
    protected array $entries;

    /**
     * @param array $entries
     */
    public function __construct(array $entries = [])
    {
        $this->entries = [];
        $this->merge($entries, false);
    }

    /**
     * @return array<string, string[]>
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
        return !empty($this->get($name));
    }

    /**
     * @param string $name
     * @param string $expectedValue
     * @return bool
     */
    public function matches(string $name, string $expectedValue): bool
    {
        foreach ($this->get($name) as $value) {
            if ($value === $expectedValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return array
     */
    public function get(string $name): array
    {
        return $this->entries[strtoupper($name)]['values'] ?? [];
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getFirst(string $name): ?string
    {
        return $this->entries[strtoupper($name)]['values'][0] ?? null;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return void
     */
    public function set(string $name, string $value, bool $replace = true): void
    {
        $key = strtoupper($name);
        $values = [$value];

        if (!$replace && $this->has($key)) {
            $values = array_merge($this->entries[$key]['values'], $values);
        }

        $this->entries[$key] = compact('name', 'values');
    }

    /**
     * @param array $entries
     * @param bool $replace
     * @return void
     */
    public function merge(array $entries, bool $replace = true): void
    {
        foreach ($entries as $name => $entry) {
            $this->set($name, $entry, $replace);
        }
    }

    /**
     * @param string $name
     * @return void
     */
    public function delete(string $name): void
    {
        unset($this->entries[strtoupper($name)]);
    }

    /**
     * @return array
     */
    protected function caseSensitiveEntries(): array
    {
        $entries = [];
        foreach ($this->entries as $data) {
            $entries[$data['name']] = $data['values'];
        }
        return $entries;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $raw = '';
        foreach ($this->entries as $data) {
            foreach ($data['values'] as $value) {
                $raw.= $data['name'].': '.$value.Request::CRLF;
            }
        }
        return $raw;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
