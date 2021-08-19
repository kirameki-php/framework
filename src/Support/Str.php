<?php declare(strict_types=1);

namespace Kirameki\Support;

use DateTimeInterface;
use Kirameki\Support\Concerns;
use Ramsey\Uuid\Uuid;
use function array_map;
use function explode;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_object;
use function is_resource;
use function is_string;
use function get_class;
use function get_resource_type;
use function lcfirst;
use function ltrim;
use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function preg_split;
use function rtrim;
use function spl_object_hash;
use function str_contains;
use function str_ends_with;
use function str_pad;
use function str_replace;
use function str_starts_with;
use function strrev;
use function substr_replace;
use function trim;
use function ucwords;

class Str
{
    use Concerns\Macroable;

    public const Encoding = 'UTF-8';

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function after(string $string, string $search): string
    {
        // If empty string is searched, return the string as is since there is nothing to trim.
        if ($search === '') {
            return $string;
        }

        $pos = mb_strpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return mb_substr($string, $pos + 1);
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function afterLast(string $string, string $search): string
    {
        // If empty string is searched, return the string as is since there is nothing to trim.
        if ($search === '') {
            return $string;
        }

        $pos = mb_strrpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return mb_substr($string, $pos + 1);
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function before(string $string, string $search): string
    {
        // If empty string is searched, return the string as is since there is nothing to search.
        if ($search === '') {
            return $string;
        }

        $pos = mb_strpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return mb_substr($string, 0, $pos);
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function beforeLast(string $string, string $search): string
    {
        // If empty string is searched, return the string as is since there is nothing to search.
        if ($search === '') {
            return $string;
        }

        $pos = mb_strrpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return mb_substr($string, 0, $pos);
    }

    /**
     * @param $string
     * @return string
     */
    public static function camelCase($string): string
    {
        return lcfirst(static::pascalCase($string));
    }

    /**
     * @param string $string
     * @return string
     */
    public static function capitalize(string $string): string
    {
        return ucfirst($string);
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
    public static function delete(string $string, string $search, ?int $limit = null): string
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
     * @param int $amount
     * @return string
     */
    public static function first(string $string, int $amount): string
    {
        return mb_substr($string, 0, $amount, self::Encoding);
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function from(string $string, int $position): string
    {
        return mb_substr($string, $position, null, self::Encoding);
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
     * @param string $padding
     * @param string $separator
     * @return string
     */
    public static function indent(string $string, string $padding = '    ', string $separator = "\n"): string
    {
        $parts = explode($separator, $string);
        $formatted = array_map(static fn(string $part) => $padding.$part, $parts);
        return implode($separator, $formatted);
    }

    /**
     * @param string $string
     * @param int $position
     * @param string $insert
     * @return string
     */
    public static function insert(string $string, int $position, string $insert): string
    {
        return
            mb_substr($string, 0, $position, self::Encoding).
            $insert.
            mb_substr($string, $position, null, self::Encoding);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string): string
    {
        $converting = preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $converting = str_replace([' ', '_'], '-', $converting);
        return mb_strtolower($converting, self::Encoding);
    }

    /**
     * @param string $string
     * @param int $amount
     * @return string
     */
    public static function last(string $string, int $amount): string
    {
        $size = mb_strlen($string, self::Encoding);
        return mb_substr($string, $size - $amount, $size, self::Encoding);
    }

    /**
     * @param string $string
     * @return int
     */
    public static function length(string $string): int
    {
        return mb_strlen($string, self::Encoding);
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
     * @param string $string
     * @return StringBuilder
     */
    public static function of(string $string = ''): StringBuilder
    {
        return new StringBuilder($string);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padBoth(string $string, int $length, string $pad = ' '): string
    {
        return str_pad($string, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padLeft(string $string, int $length, string $pad = ' '): string
    {
        return str_pad($string, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padRight(string $string, int $length, string $pad = ' '): string
    {
        return str_pad($string, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function pascalCase(string $string): string
    {
        return str_replace(['-', '_', ' '], '', ucwords($string, '-_ '));
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
     */
    public static function replace(string $string, string $search, string $replace): string
    {
        return str_replace($search, $replace, $string);
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
     */
    public static function replaceFirst(string $string, string $search, string $replace): string
    {
        $pos = strpos($string, $search);
        return $pos !== false
            ? substr_replace($string, $replace, $pos, strlen($search))
            : $string;
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
     */
    public static function replaceLast(string $string, string $search, string $replace): string
    {
        $pos = strrpos($string, $search);
        return $pos !== false
            ? substr_replace($string, $replace, $pos, strlen($search))
            : $string;
    }

    /**
     * @param string $string
     * @param string $pattern
     * @param string $replace
     * @param int|null $limit
     * @return string
     */
    public static function replaceMatch(string $string, string $pattern, string $replace, ?int $limit = null): string
    {
        return preg_replace($pattern, $replace, $string, $limit ?? -1);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function reverse(string $string): string
    {
        return strrev($string);
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
        $converting = preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $converting = str_replace([' ', '-'], '_', $converting);
        return mb_strtolower($converting, self::Encoding);
    }

    /**
     * @param string $string
     * @param string|string[] $separator
     * @param int|null $limit
     * @return string[]
     */
    public static function split(string $string, string|array $separator, ?int $limit = null): array
    {
        if (is_array($separator)) {
            $pattern = '/('.implode('|', array_map('preg_quote', $separator)).')/';
            return preg_split($pattern, $string, $limit ?? -1);
        }

        return $limit !== null
            ? explode($separator, $string, $limit)
            : explode($separator, $string);
    }

    /**
     * @param string $string
     * @param int $offset
     * @param int|null $length
     * @return string
     */
    public static function substring(string $string, int $offset, ?int $length = null): string
    {
        return mb_substr($string, $offset, $length, self::Encoding);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function titleize(string $string): string
    {
        return ucwords($string);
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function to(string $string, int $position): string
    {
        return mb_substr($string, 0, $position, self::Encoding);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function toLower(string $string): string
    {
        return mb_strtolower($string, self::Encoding);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function toUpper(string $string): string
    {
        return mb_strtoupper($string, self::Encoding);
    }

    /**
     * @param string $string
     * @param string $character
     * @return string
     */
    public static function trim(string $string, string $character = " \t\n\r\0\x0B"): string
    {
        return trim($string, $character);
    }

    /**
     * @param string $string
     * @param string $character
     * @return string
     */
    public static function trimStart(string $string, string $character = " \t\n\r\0\x0B"): string
    {
        return ltrim($string, $character);
    }

    /**
     * @param string $string
     * @param string $character
     * @return string
     */
    public static function trimEnd(string $string, string $character = " \t\n\r\0\x0B"): string
    {
        return rtrim($string, $character);
    }

    /**
     * @param string $string
     * @param int $size
     * @param string $ellipsis
     * @return string
     */
    public static function truncate(string $string, int $size, string $ellipsis = '...'): string
    {
        return mb_strcut($string, 0, $size, self::Encoding).$ellipsis;
    }

    /**
     * @param mixed $var
     * @return string
     */
    public static function typeOf(mixed $var): string
    {
        if (is_null($var)) return "null";
        if (is_bool($var)) return "bool";
        if (is_int($var)) return "int";
        if (is_float($var)) return "float";
        if (is_string($var)) return "string";
        if (is_array($var)) return "array";
        if ($var instanceof DateTimeInterface) return 'datetime';
        if (is_object($var)) return "object";
        if (is_resource($var)) return "resource";
        return "unknown type";
    }

    /**
     * @return string
     */
    public static function uuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * @param mixed $var
     * @return string
     */
    public static function valueOf(mixed $var): string
    {
        if (is_null($var)) return 'null';
        if (is_bool($var)) return $var ? 'true' : 'false';
        if (is_array($var)) return Json::encode($var);
        if ($var instanceof DateTimeInterface) return $var->format(DATE_RFC3339_EXTENDED);
        if (is_object($var)) return get_class($var).':'.spl_object_hash($var);
        if (is_resource($var)) return get_resource_type($var);
        return (string) $var;
    }

    /**
     * @param string $string
     * @param int $width
     * @param string $break
     * @param bool $overflow
     * @return string
     */
    public static function wrap(string $string, int $width = 80, string $break = "\n", bool $overflow = false): string
    {
        return wordwrap($string, $width, $break, !$overflow);
    }
}
