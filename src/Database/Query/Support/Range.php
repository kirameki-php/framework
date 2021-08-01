<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

class Range
{
    public mixed $lowerBound;
    public bool $lowerClosed;
    public mixed $upperBound;
    public bool $upperClosed;

    /**
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function closed($lower, $upper): static
    {
        return new static($lower, true, $upper, true);
    }

    /**
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function open($lower, $upper): static
    {
        return new static($lower, false, $upper, false);
    }

    /**
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function halfOpen($lower, $upper): static
    {
        return new static($lower, true, $upper, false);
    }

    /**
     * @see closed()
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function included($lower, $upper): static
    {
        return static::closed($lower, $upper);
    }

    /**
     * @see open()
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function excluded($lower, $upper): static
    {
        return static::open($lower, $upper);
    }

    /**
     * @see halfOpen()
     * @param $lower
     * @param $upper
     * @return static
     */
    public static function endExcluded($lower, $upper): static
    {
        return static::halfOpen($lower, $upper);
    }

    /**
     * @param mixed $lowerBound
     * @param bool $lowerClosed
     * @param mixed $upperBound
     * @param bool $upperClosed
     */
    public function __construct(mixed $lowerBound, bool $lowerClosed, mixed $upperBound, bool $upperClosed)
    {
        $this->lowerBound = $lowerBound;
        $this->lowerClosed = $lowerClosed;
        $this->upperBound = $upperBound;
        $this->upperClosed = $upperClosed;
    }

    /**
     * @return array
     */
    public function getBounds(): array
    {
        return [
            $this->lowerBound,
            $this->upperBound,
        ];
    }
}
