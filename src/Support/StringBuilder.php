<?php declare(strict_types=1);

namespace Kirameki\Support;

use Kirameki\Support\Concerns;
use Stringable;
use Symfony\Component\VarDumper\VarDumper;
use function basename;
use function dirname;

class StringBuilder implements Stringable
{
    use Concerns\Macroable;

    /**
     * @var string
     */
    protected string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    /**
     * @param string $search
     * @return $this
     */
    public function after(string $search): static
    {
        $this->value = Str::after($this->value, $search);
        return $this;
    }

    /**
     * @param int $position
     * @return $this
     */
    public function afterIndex(int $position): static
    {
        $this->value = Str::afterIndex($this->value, $position);
        return $this;
    }

    /**
     * @param string $search
     * @return $this
     */
    public function afterLast(string $search): static
    {
        $this->value = Str::afterLast($this->value, $search);
        return $this;
    }

    /**
     * @param bool|float|int|string|null $string
     * @return $this
     */
    public function append(string|bool|int|float|null $string): static
    {
        $this->value.= $string;
        return $this;
    }

    /**
     * @param string $format
     * @param bool|float|int|string|null ...$values
     * @return $this
     */
    public function appendFormat(string $format, ...$values): static
    {
        $this->value.= sprintf($format, ...$values);
        return $this;
    }

    /**
     * @param string $suffix
     * @return $this
     */
    public function basename(string $suffix = ''): static
    {
        $this->value = basename($this->value, $suffix);
        return $this;
    }

    /**
     * @param string $search
     * @return static
     */
    public function before(string $search): static
    {
        $this->value = Str::before($this->value, $search);
        return $this;
    }

    /**
     * @param int $position
     * @return $this
     */
    public function beforeIndex(int $position): static
    {
        $this->value = Str::beforeIndex($this->value, $position);
        return $this;
    }

    /**
     * @param string $search
     * @return static
     */
    public function beforeLast(string $search): static
    {
        $this->value = Str::beforeLast($this->value, $search);
        return $this;
    }

    /**
     * @return static
     */
    public function camelCase(): static
    {
        $this->value = Str::camelCase($this->value);
        return $this;
    }

    /**
     * @return $this
     */
    public function capitalize(): static
    {
        $this->value = Str::capitalize($this->value);
        return $this;
    }

    /**
     * @param string $needle
     * @return bool
     */
    public function contains(string $needle): bool
    {
        return Str::contains($this->value, $needle);
    }

    /**
     * @param array<string> $needles
     * @return bool
     */
    public function containsAll(array $needles): bool
    {
        return Str::containsAll($this->value, $needles);
    }

    /**
     * @param array<string> $needles
     * @return bool
     */
    public function containsAny(array $needles): bool
    {
        return Str::containsAny($this->value, $needles);
    }

    /**
     * @param string $pattern
     * @return bool
     */
    public function containsPattern(string $pattern): bool
    {
        return Str::containsPattern($this->value, $pattern);
    }

    /**
     * @return $this
     */
    public function dd(): static
    {
        if ($this->dump()) { /** @phpstan-ignore-line */
            exit(1);
        }
        return $this; /** @phpstan-ignore-line */
    }

    /**
     * @param string $search
     * @param int|null $limit
     * @return static
     */
    public function delete(string $search, int $limit = null): static
    {
        $this->value = Str::delete($this->value, $search, $limit ?? -1);
        return $this;
    }

    /**
     * @return $this
     */
    public function dump(): static
    {
        VarDumper::dump($this->value);
        return $this;
    }

    /**
     * @param int<1, max> $levels
     * @return $this
     */
    public function dirname(int $levels = 1): static
    {
        $this->value = dirname($this->value, $levels);
        return $this;
    }

    /**
     * @param int $position
     * @param string $insert
     * @return $this
     */
    public function insert(string $insert, int $position): static
    {
        $this->value = Str::insert($this->value, $insert, $position);
        return $this;
    }

    /**
     * @return static
     */
    public function kebabCase(): static
    {
        $this->value = Str::kebabCase($this->value);
        return $this;
    }

    /**
     * @return int
     */
    public function length(): int
    {
        return Str::length($this->value);
    }

    /**
     * @param int $length
     * @param string $pad
     * @return $this
     */
    public function padBoth(int $length, string $pad = ' '): static
    {
        $this->value = Str::padBoth($this->value, $length, $pad);
        return $this;
    }

    /**
     * @param int $length
     * @param string $pad
     * @return $this
     */
    public function padLeft(int $length, string $pad = ' '): static
    {
        $this->value = Str::padLeft($this->value, $length, $pad);
        return $this;
    }

    /**
     * @param int $length
     * @param string $pad
     * @return $this
     */
    public function padRight(int $length, string $pad = ' '): static
    {
        $this->value = Str::padRight($this->value, $length, $pad);
        return $this;
    }

    /**
     * @return static
     */
    public function pascalCase(): static
    {
        $this->value = Str::pascalCase($this->value);
        return $this;
    }

    /**
     * @param string $search
     * @return bool|int
     */
    public function position(string $search): bool|int
    {
        return Str::position($this->value, $search);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function prepend(string $string): static
    {
        $this->value = $string.$this->value;
        return $this;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function repeat(int $times): static
    {
        $this->value = Str::repeat($this->value, $times);
        return $this;
    }

    /**
     * @param string $search
     * @param string $replace
     * @return $this
     */
    public function replace(string $search, string $replace): static
    {
        $this->value = Str::replace($this->value, $search, $replace);
        return $this;
    }

    /**
     * @param string $search
     * @param string $replace
     * @return $this
     */
    public function replaceFirst(string $search, string $replace): static
    {
        $this->value = Str::replaceFirst($this->value, $search, $replace);
        return $this;
    }

    /**
     * @param string $search
     * @param string $replace
     * @return $this
     */
    public function replaceLast(string $search, string $replace): static
    {
        $this->value = Str::replaceLast($this->value, $search, $replace);
        return $this;
    }

    /**
     * @param string $pattern
     * @param string $replace
     * @param int $limit
     * @return $this
     */
    public function replaceMatch(string $pattern, string $replace, int $limit = null): static
    {
        $this->value = Str::replaceMatch($this->value, $pattern, $replace, $limit ?? -1);
        return $this;
    }

    /**
     * @return $this
     */
    public function reverse(): static
    {
        $this->value = Str::reverse($this->value);
        return $this;
    }

    /**
     * @return static
     */
    public function snakeCase(): static
    {
        $this->value = Str::snakeCase($this->value);
        return $this;
    }

    /**
     * @param non-empty-string $separator
     * @param int|null $limit
     * @return array<int, string>
     */
    public function split(string $separator, int $limit = null): array
    {
        return Str::split($this->value, $separator, $limit);
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return $this
     */
    public function substring(int $offset, ?int $length = null): static
    {
        $this->value = Str::substring($this->value, $offset, $length);
        return $this;
    }

    /**
     * @return $this
     */
    public function titleize(): static
    {
        $this->value = Str::titleize($this->value);
        return $this;
    }

    /**
     * @return $this
     */
    public function toLower(): static
    {
        $this->value = Str::toLower($this->value);
        return $this;
    }

    /**
     * @return $this
     */
    public function toUpper(): static
    {
        $this->value = Str::toUpper($this->value);
        return $this;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function transform(callable $callback): static
    {
        $this->value = (string) $callback($this->value);
        return $this;
    }

    /**
     * @param string $characters
     * @return $this
     */
    public function trim(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->value = Str::trim($this->value, $characters);
        return $this;
    }

    /**
     * @param string $characters
     * @return $this
     */
    public function trimStart(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->value = Str::trimStart($this->value, $characters);
        return $this;
    }

    /**
     * @param string $characters
     * @return $this
     */
    public function trimEnd(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->value = Str::trimEnd($this->value, $characters);
        return $this;
    }

    /**
     * @param int $size
     * @param string $ellipsis
     * @return $this
     */
    public function truncate(int $size, string $ellipsis = '...'): static
    {
        $this->value = Str::truncate($this->value, $size, $ellipsis);
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
