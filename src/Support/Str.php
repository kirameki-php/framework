<?php declare(strict_types=1);

namespace Kirameki\Support;


use Ramsey\Uuid\Uuid;

class Str
{
    use Concerns\Macroable;

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function after(string $string, string $search): string
    {
        $pos = strpos($string, $search);
        return $pos !== false ? substr($string, $pos + 1) : '';
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function afterLast(string $string, string $search): string
    {
        $pos = strrpos($string, $search);
        return $pos !== false ? substr($string, $pos + 1) : '';
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function before(string $string, string $search): string
    {
        $pos = strpos($string, $search);
        return $pos !== false ? substr($string, 0, $pos) : $string;
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function beforeLast(string $string, string $search): string
    {
        $pos = strrpos($string, $search);
        return $pos !== false ? substr($string, 0, $pos) : $string;
    }

    /**
     * @param $string
     * @return string
     */
    public static function camelCase($string): string
    {
        return lcfirst(Str::pascalCase($string));
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * @param string $string
     * @param string $search
     * @param int|null $limit
     * @return string
     */
    public static function delete(string $string, string $search, int $limit = null): string
    {
        $offset = 0;
        $length = strlen($search);
        $limit ??= INF;
        while($limit > 0) {
            $pos = strpos($string, $search, $offset);
            if ($pos === false) {
                break;
            }
            $string = substr_replace($string, '', $pos, $length);
            $offset = $pos - $length;
            $limit--;
        }
        return $string;
    }

    /**
     * @param string $haystack
     * @param string|string[] $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string|array $needle): bool
    {
        foreach (Arr::wrap($needle) as $each) {
            if (str_ends_with($haystack, $each)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return bool
     */
    public static function hasMatch(string $string, string $pattern): bool
    {
        return (bool) preg_match($pattern, $string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string): string
    {
        $hyphenated = preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $hyphenated = str_replace([' ', '_'], '-', $hyphenated);
        return strtolower($hyphenated);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return array
     */
    public static function match(string $string, string $pattern): array
    {
        $match = [];
        preg_match($pattern, $string, $match);
        return $match;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function notContains(string $haystack, string $needle): bool
    {
        return !static::contains($haystack, $needle);
    }

    /**
     * @param $string
     * @return string
     */
    public static function pascalCase($string): string
    {
        return str_replace(['-', '_', ' '], '', ucwords($string, '-_ '));
    }

    /**
     * @param string $haystack
     * @param string|string[] $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string|array $needle): bool
    {
        foreach (Arr::wrap($needle) as $each) {
            if (str_starts_with($haystack, $each)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function snakeCase(string $string): string
    {
        $hyphenated = preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $hyphenated = str_replace([' ', '-'], '_', $hyphenated);
        return strtolower($hyphenated);    }

    /**
     * @param string $string
     * @param string $separator
     * @param int|null $limit
     * @return string[]
     */
    public static function split(string $string, string $separator, int $limit = null): array
    {
        return $limit !== null
            ? explode($separator, $string, $limit)
            : explode($separator, $string);
    }

    /**
     * @return string
     */
    public static function uuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}
