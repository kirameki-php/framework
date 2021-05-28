<?php

namespace Kirameki\Support;

class Json
{
    /**
     * @param mixed $data
     * @param int $options
     * @param int $depth
     * @return string
     */
    public static function encode(mixed $data, int $options = 0, int $depth = 512): string
    {
        $options = $options | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        return json_encode($data, JSON_THROW_ON_ERROR | $options, $depth);
    }

    /**
     * @param mixed $json
     * @param int $depth
     * @return array
     */
    public static function decode($json, int $depth = 512): array
    {
        return json_decode($json, true, $depth, JSON_THROW_ON_ERROR);
    }
}
