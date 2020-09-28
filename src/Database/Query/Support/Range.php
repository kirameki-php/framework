<?php

namespace Kirameki\Database\Query\Support;

use Kirameki\Database\Query\Formatters\Formatter;

class Range
{
    public $lowerBound;
    public bool $lowerClosed;
    public $upperBound;
    public bool $upperClosed;

    /**
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function closed($lower, $upper)
    {
        return new static($lower, true, $upper, true);
    }

    /**
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function open($lower, $upper)
    {
        return new static($lower, false, $upper, false);
    }

    /**
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function halfOpen($lower, $upper)
    {
        return new static($lower, true, $upper, false);
    }

    /**
     * @see closed()
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function included($lower, $upper)
    {
        return static::closed($lower, $upper);
    }

    /**
     * @see open()
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function excluded($lower, $upper)
    {
        return static::open($lower, $upper);
    }

    /**
     * @see halfOpen()
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function endExcluded($lower, $upper)
    {
        return static::halfOpen($lower, $upper);
    }

    /**
     * @param $lowerBound
     * @param bool $lowerClosed
     * @param $upperBound
     * @param bool $upperClosed
     */
    public function __construct($lowerBound, bool $lowerClosed, $upperBound, bool $upperClosed)
    {
        $this->lowerBound = $lowerBound;
        $this->lowerClosed = $lowerClosed;
        $this->upperBound = $upperBound;
        $this->upperClosed = $upperClosed;
    }

    /**
     * @return array
     */
    public function getBounds()
    {
        return [
            $this->lowerBound,
            $this->upperBound,
        ];
    }
}
