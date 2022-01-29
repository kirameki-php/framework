<?php declare(strict_types=1);

namespace Kirameki\Support;

use function json_decode;
use function json_encode;
use function file_get_contents;

class Json
{
    /**
     * @param mixed $data
     * @param int $options
     * @param int<1, max> $depth
     * @return string
     */
    public static function encode(mixed $data, int $options = 0, int $depth = 512): string
    {
        $options = $options | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        return json_encode($data, JSON_THROW_ON_ERROR | $options, $depth);
    }

    /**
     * @param string $json
     * @param int<1, max> $depth
     * @return array<mixed>
     */
    public static function decode(string $json, int $depth = 512): array
    {
        return json_decode($json, true, $depth, JSON_THROW_ON_ERROR); /** @phpstan-ignore-line */
    }

    /**
     * @param string $path
     * @return array<mixed>
     */
    public static function decodeFile(string $path): array
    {
        return static::decode((string) file_get_contents($path));
    }
}
