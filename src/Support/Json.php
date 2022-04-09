<?php declare(strict_types=1);

namespace Kirameki\Support;

use function json_decode;
use function json_encode;
use function file_get_contents;

class Json
{
    protected static int $encodeOptions =
        JSON_PRESERVE_ZERO_FRACTION |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES;

    /**
     * @param int<1, max> $depth
     */
    public static function encode(mixed $data, int $options = 0, int $depth = 512): string
    {
        return json_encode($data, $options | static::$encodeOptions | JSON_THROW_ON_ERROR, $depth);
    }

    public static function setEncodeOptions(int $options): void
    {
        static::$encodeOptions = $options;
    }

    public static function getEncodeOptions(): int
    {
        return static::$encodeOptions;
    }

    /**
     * @param int<1, max> $depth
     */
    public static function decode(string $json, int $depth = 512): mixed
    {
        return json_decode($json, true, $depth, JSON_THROW_ON_ERROR);
    }

    public static function decodeFile(string $path): mixed
    {
        return static::decode((string) file_get_contents($path));
    }
}
