<?php

namespace Kirameki\Support;


class Arr
{
    /**
     * @param array $arr
     * @return array
     */
    public static function compact(array $arr)
    {
        return array_filter($arr, static fn($s) => $s !== null);
    }
}