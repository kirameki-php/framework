<?php declare(strict_types=1);

namespace Kirameki\Support\String;

use DateTimeInterface;
use Kirameki\Support\Arr;
use Kirameki\Support\Concerns;
use Kirameki\Support\Json;
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
use function mb_strlen;
use function mb_strpos;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function spl_object_hash;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function substr_replace;
use function ucwords;

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
        $pos = mb_strrpos($string, $search, 0, 'UTF-8');
        return $pos !== false ? mb_substr($string, $pos + 1, null, 'UTF-8') : '';
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function afterLast(string $string, string $search): string
    {
        $pos = mb_strrpos($string, $search, 0, 'UTF-8');
        return $pos !== false ? mb_substr($string, $pos + 1, null, 'UTF-8') : '';
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function before(string $string, string $search): string
    {
        $pos = mb_strpos($string, $search, 0, 'UTF-8');
        return $pos !== false ? mb_substr($string, 0, $pos, 'UTF-8') : $string;
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function beforeLast(string $string, string $search): string
    {
        $pos = mb_strrpos($string, $search, 0, 'UTF-8');
        return $pos !== false ? mb_substr($string, 0, $pos, 'UTF-8') : $string;
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
        $length = mb_strlen($search, 'UTF-8');
        $limit ??= INF;
        while($limit > 0) {
            $pos = mb_strpos($string, $search, $offset, 'UTF-8');
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
        return mb_substr($string, 0, $amount, 'UTF-8');
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function from(string $string, int $position): string
    {
        return mb_substr($string, $position, null, 'UTF-8');
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
            mb_substr($string, 0, $position, 'UTF-8').
            $insert.
            mb_substr($string, $position, null, 'UTF-8');
    }

    /**
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string): string
    {
        $converting = preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $converting = str_replace([' ', '_'], '-', $converting);
        return mb_strtolower($converting, 'UTF-8');
    }

    /**
     * @param string $string
     * @param int $amount
     * @return string
     */
    public static function last(string $string, int $amount): string
    {
        $size = mb_strlen($string, 'UTF-8');
        return mb_substr($string, $size - $amount, $size, 'UTF-8');
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
        $converting = preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $converting = str_replace([' ', '-'], '_', $converting);
        return mb_strtolower($converting, 'UTF-8');
    }

    /**
     * @param string $string
     * @param string $separator
     * @param int|null $limit
     * @return string[]
     */
    public static function split(string $string, string $separator, ?int $limit = null): array
    {
        return $limit !== null
            ? explode($separator, $string, $limit)
            : explode($separator, $string);
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
        return mb_substr($string, 0, $position, 'UTF-8');
    }

    /**
     * @param string $string
     * @param int $size
     * @param string $ellipsis
     * @return string
     */
    public static function truncate(string $string, int $size, string $ellipsis = '...'): string
    {
        return mb_strcut($string, 0, $size, 'UTF-8').$ellipsis;
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
        if (is_resource($var)) return get_resource_type($var);
        if ($var instanceof DateTimeInterface) return $var->format(DATE_RFC3339_EXTENDED);
        if (is_object($var)) return get_class($var).':'.spl_object_hash($var);
        return (string) $var;
    }
}
