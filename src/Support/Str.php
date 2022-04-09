<?php declare(strict_types=1);

namespace Kirameki\Support;

use DateTimeInterface;
use Kirameki\Support\Concerns;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Traversable;
use UnitEnum;
use Webmozart\Assert\Assert;
use function array_map;
use function ceil;
use function explode;
use function floor;
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
use function grapheme_strlen;
use function grapheme_strpos;
use function grapheme_strrpos;
use function grapheme_substr;
use function lcfirst;
use function ltrim;
use function mb_strcut;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function preg_replace;
use function preg_split;
use function rtrim;
use function spl_object_hash;
use function str_contains;
use function str_ends_with;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
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
        $pos = grapheme_strpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return (string) grapheme_substr($string, $pos + grapheme_strlen($search));
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function afterIndex(string $string, int $position): string
    {
        return (string) grapheme_substr($string, $position);
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

        $pos = grapheme_strrpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return (string) grapheme_substr($string, $pos + grapheme_strlen($search));
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

        $pos = grapheme_strpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return (string) grapheme_substr($string, 0, $pos);
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function beforeIndex(string $string, int $position): string
    {
        return (string) grapheme_substr($string, 0, $position);
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function beforeLast(string $string, string $search): string
    {
        $pos = grapheme_strrpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return (string) grapheme_substr($string, 0, $pos);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function camelCase(string $string): string
    {
        return lcfirst(static::pascalCase($string));
    }

    /**
     * @param string $string
     * @return string
     */
    public static function capitalize(string $string): string
    {
        $firstChar = mb_strtoupper((string) grapheme_substr($string, 0, 1));
        $otherChars = grapheme_substr($string, 1);
        return $firstChar.$otherChars;
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
     * @param string $haystack
     * @param iterable<array-key, string> $needles
     * @return bool
     */
    public static function containsAll(string $haystack, iterable $needles): bool
    {
        $needles = Arr::from($needles);

        Assert::minCount($needles, 1);

        foreach ($needles as $needle) {
            if(!str_contains($haystack, $needle)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $haystack
     * @param iterable<array-key, string> $needles
     * @return bool
     */
    public static function containsAny(string $haystack, iterable $needles): bool
    {
        $needles = Arr::from($needles);

        Assert::minCount($needles, 1);

        foreach ($needles as $needle) {
            if(str_contains($haystack, $needle)) {
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
    public static function containsPattern(string $string, string $pattern): bool
    {
        return (bool) preg_match($pattern, $string);
    }

    /**
     * @param string $string
     * @param string $search
     * @param int $limit
     * @return string
     */
    public static function delete(string $string, string $search, int $limit = -1): string
    {
        return static::replace($string, $search, '', $limit);
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
     * @param string $insert
     * @param int $position
     * @return string
     */
    public static function insert(string $string, string $insert, int $position): string
    {
        return
            grapheme_substr($string, 0, $position).
            $insert.
            grapheme_substr($string, $position);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $converting = (string) preg_replace('/[-_\s]+/', '-', $converting);
        return mb_strtolower($converting, self::Encoding);
    }

    /**
     * @param string $string
     * @return int
     */
    public static function length(string $string): int
    {
        return (int) grapheme_strlen($string);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return array<int, array<string>>
     */
    public static function match(string $string, string $pattern): array
    {
        $match = [];
        preg_match($pattern, $string, $match);
        return $match;
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return array<int, array<string>>
     */
    public static function matchAll(string $string, string $pattern): array
    {
        $match = [];
        preg_match_all($pattern, $string, $match);
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
        return static::pad($string, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padLeft(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padRight(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @param int $type
     * @return string
     */
    public static function pad(string $string, int $length, string $pad = ' ', int $type = STR_PAD_RIGHT): string
    {
        if ($length <= 0) {
            return $string;
        }

        $padLength = grapheme_strlen($pad);

        if ($padLength === 0) {
            return $string;
        }

        if ($type === STR_PAD_RIGHT) {
            $repeat = (int) ceil($length / $padLength);
            return (string) grapheme_substr($string.str_repeat($pad, $repeat), 0, $length);
        }

        if ($type === STR_PAD_LEFT) {
            $repeat = (int) ceil($length / $padLength);
            return (string) grapheme_substr(str_repeat($pad, $repeat).$string, -$length);
        }

        if ($type === STR_PAD_BOTH) {
            $halfLengthFraction = ($length - grapheme_strlen($string)) / 2;
            $halfRepeat = (int) ceil($halfLengthFraction / $padLength);
            $prefixLength = (int) floor($halfLengthFraction);
            $suffixLength = (int) ceil($halfLengthFraction);
            $prefix = grapheme_substr(str_repeat($pad, $halfRepeat), 0, $prefixLength);
            $suffix = grapheme_substr(str_repeat($pad, $halfRepeat), 0, $suffixLength);
            return $prefix.$string.$suffix;
        }

        throw new RuntimeException('Invalid padding type: '.$type);
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
     * @param int $offset
     * @return bool|int
     */
    public static function position(string $string, string $search, int $offset = 0): bool|int
    {
        return grapheme_strpos($string, $search, $offset);
    }

    /**
     * @param string $string
     * @param int $times
     * @return string
     */
    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @param int $limit
     * @return string
     */
    public static function replace(string $string, string $search, string $replace, int $limit = -1): string
    {
        if ($search === '') {
            return $string;
        }

        return static::replaceMatch($string, "/\Q$search\E/", $replace, $limit);
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
     */
    public static function replaceFirst(string $string, string $search, string $replace): string
    {
        if ($search === '') {
            return $string;
        }

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
        if ($search === '') {
            return $string;
        }

        $pos = strrpos($string, $search);
        return $pos !== false
            ? substr_replace($string, $replace, $pos, strlen($search))
            : $string;
    }

    /**
     * @param string $string
     * @param string $pattern
     * @param string $replace
     * @param int $limit
     * @return string
     */
    public static function replaceMatch(string $string, string $pattern, string $replace, int $limit = -1): string
    {
        if ($string === '') {
            return $string;
        }

        return (string) preg_replace($pattern, $replace, $string, $limit);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function reverse(string $string): string
    {
        $length = grapheme_strlen($string);

        // strrev($string) can only reverse bytes, so it only works for single byte chars.
        // So call strrev only if we can confirm that it only contains single byte chars.
        if ($length === strlen($string)) {
            return strrev($string);
        }

        $parts = [];
        for ($i = $length - 1; $i >= 0; $i--) {
            $parts[] = grapheme_substr($string, $i, 1);
        }
        return implode('', $parts);
    }

    /**
     * @param string $haystack
     * @param string|list<string> $needle
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
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $converting = (string) str_replace([' ', '-'], '_', $converting);
        return mb_strtolower($converting, self::Encoding);
    }

    /**
     * @param string $string
     * @param non-empty-string|array<non-empty-string> $separator
     * @param int|null $limit
     * @return array<int, string>
     */
    public static function split(string $string, string|array $separator, ?int $limit = null): array
    {
        if (is_array($separator)) {
            $pattern = '/('.implode('|', array_map('preg_quote', $separator)).')/';
            $splits = preg_split($pattern, $string, $limit ?? -1);
            if ($splits === false) {
                throw new RuntimeException('You should never reach here.');
            }
            return $splits;
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
        return (string) grapheme_substr($string, $offset, $length);
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
        if (is_null($var)) {
            return 'null';
        }

        if (is_bool($var)) {
            return 'bool';
        }

        if (is_int($var)) {
            return 'int';
        }

        if (is_float($var)) {
            return 'float';
        }

        if (is_string($var)) {
            return 'string';
        }

        if (is_array($var)) {
            return 'array';
        }

        if ($var instanceof DateTimeInterface) {
            return 'datetime';
        }

        if ($var instanceof UnitEnum) {
            return 'enum';
        }

        if (is_object($var)) {
            return 'object';
        }

        if (is_resource($var)) {
            return "resource";
        }

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
        if (is_null($var)) {
            return 'null';
        }

        if (is_bool($var)) {
            return $var ? 'true' : 'false';
        }

        if (is_int($var)) {
            return (string) $var;
        }

        if (is_float($var)) {
            return (string) $var;
        }

        if (is_string($var)) {
            return $var;
        }

        if (is_array($var)) {
            return Json::encode($var);
        }

        if ($var instanceof Traversable) {
            return Json::encode(iterator_to_array($var));
        }

        if ($var instanceof DateTimeInterface) {
            return $var->format(DATE_RFC3339_EXTENDED);
        }

        if ($var instanceof UnitEnum) {
            return $var->name;
        }

        if (is_object($var)) {
            return get_class($var) . ':' . spl_object_hash($var);
        }

        if (is_resource($var)) {
            return get_resource_type($var);
        }

        throw new RuntimeException('Unknown type: '.$var);
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
