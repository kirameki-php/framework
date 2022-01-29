<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use RuntimeException;
use Stringable;

class Time extends DateTimeImmutable implements JsonSerializable, Stringable
{
    public const RFC3339_HUMAN = 'Y-m-d H:i:s.u P';

    /**
     * @var Closure():static|static|null
     */
    protected static mixed $testNow = null;

    /**
     * @inheritDoc
     */
    public function __construct(string $time = null, DateTimeZone $timezone = null)
    {
        if ($time === null || $time === 'now') {
            $time = (static::invokeTestNow() ?? new DateTime)->format(self::RFC3339_HUMAN);
        }

        parent::__construct($time, $timezone);
    }

    # region Creation --------------------------------------------------------------------------------------------------

    /**
     * @return static
     */
    public function copy(): static
    {
        return clone $this;
    }

    /**
     * @param string $format
     * @param string $datetime
     * @param DateTimeZone|null $timezone
     * @return false|static
     */
    public static function createFromFormat($format, $datetime, DateTimeZone $timezone = null): static|false
    {
        /** @var DateTime $base */
        $base = DateTime::createFromFormat($format, $datetime);

        // NOTE: In DateTime class the timezone parameter and the current timezone are ignored when the time parameter
        // either contains a UNIX timestamp (e.g. 946684800) or specifies a timezone (e.g. 2010-01-28T15:00:00+02:00)
        // so we have to use setTimezone($timezone) to do the job.
        if ($timezone !== null) {
            $base = $base->setTimezone($timezone);
        }

        // NOTE: Invalid dates (ex: Feb 30th) can slip through so we handle that here
        // https://www.php.net/manual/en/datetime.getlasterrors.php#102686
        $errors = DateTime::getLastErrors();
        if ($errors !== false && $errors['error_count'] + $errors['warning_count'] === 0) {
            // TODO: more precise error handling
            throw new RuntimeException(Json::encode($errors));
        }

        return static::createFromInterface($base);
    }

    /**
     * @param DateTime $object
     * @return static
     */
    public static function createFromMutable(DateTime $object): static
    {
        return static::createFromInterface($object);
    }

    /**
     * @param DateTimeInterface $object
     * @return static
     */
    public static function createFromInterface(DateTimeInterface $object): static
    {
        return new static($object->format(static::RFC3339_HUMAN));
    }

    # endregion Creation -----------------------------------------------------------------------------------------------

    # region Mutation --------------------------------------------------------------------------------------------------

    /**
     * @param int|null $years
     * @param int|null $months
     * @param int|null $days
     * @param int|null $hours
     * @param int|null $minutes
     * @param float|null $seconds
     * @return static
     */
    public function change(
        ?int $years = null,
        ?int $months = null,
        ?int $days = null,
        ?int $hours = null,
        ?int $minutes = null,
        ?float $seconds = null
    ): static
    {
        $parts = explode(' ', $this->format('Y m d H i s u'));

        $parts[0] = $years ?? (int) $parts[0];
        $parts[1] = $months ?? (int) $parts[1];
        $parts[2] = $days ?? (int) $parts[2];
        $parts[3] = $hours ?? (int) $parts[3];
        $parts[4] = $minutes ?? (int) $parts[4];
        $parts[5] = $seconds ?? (float) ($parts[5].'.'.$parts[6]);

        return static::createFromFormat('Y m d H i s u', implode(' ', $parts));
    }

    /**
     * @param int|null $years
     * @param int|null $months
     * @param int|null $days
     * @param int|null $hours
     * @param int|null $minutes
     * @param float|null $seconds
     * @return static
     */
    public function turn(
        ?int $years = null,
        ?int $months = null,
        ?int $days = null,
        ?int $hours = null,
        ?int $minutes = null,
        ?float $seconds = null
    ): static
    {
        $modify = ($years !== null) ? "+{$years}year" : '';
        $modify.= ($months !== null) ? "+{$months}month" : '';
        $modify.= ($days !== null) ? "+{$days}day" : '';
        $modify.= ($hours !== null) ? "+{$hours}hour" : '';
        $modify.= ($minutes !== null) ? "+{$minutes}minute" : '';
        $modify.= ($seconds !== null) ? "+{$seconds}seconds" : '';
        return $this->modify($modify);
    }

    /**
     * @param DateTimeInterface|null $lower
     * @param DateTimeInterface|null $upper
     * @return static
     */
    public function clamp(?DateTimeInterface $lower = null, ?DateTimeInterface $upper = null): static
    {
        if ($lower !== null && $this < $lower) {
            return static::createFromInterface($lower);
        }

        if ($upper !== null && $this > $upper) {
            return static::createFromInterface($upper);
        }

        return $this->copy();
    }

    # endregion Mutation -----------------------------------------------------------------------------------------------

    # region Comparison ------------------------------------------------------------------------------------------------

    /**
     * @param DateTimeInterface $min
     * @param DateTimeInterface $max
     * @return bool
     */
    public function between(DateTimeInterface $min, DateTimeInterface $max): bool
    {
        return $min <= $this && $this <= $max;
    }

    # endregion Comparison ---------------------------------------------------------------------------------------------

    # region Conversion ------------------------------------------------------------------------------------------------

    /**
     * @return float
     */
    public function getPreciseTimestamp(): float
    {
        return (float) $this->format('U.u');
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toHttpFormat(): string
    {
        return $this->format(self::RFC822);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->format(self::RFC3339_HUMAN);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    # endregion Conversion ---------------------------------------------------------------------------------------------

    # region Testing ---------------------------------------------------------------------------------------------------

    /**
     * @param static|Closure():static|null $now
     */
    public static function setTestNow(Time|Closure|null $now): void
    {
        self::$testNow = $now;
    }

    /**
     * @return bool
     */
    public static function hasTestNow(): bool
    {
        return self::$testNow !== null;
    }

    /**
     * @return Time|null
     */
    protected static function invokeTestNow(): ?Time
    {
        $now = static::$testNow;
        if ($now instanceof Closure) {
            return $now();
        }
        return $now;
    }

    # endregion Testing ------------------------------------------------------------------------------------------------

    # region Relative --------------------------------------------------------------------------------------------------

    /**
     * @return static
     */
    public static function now(): static
    {
        return new static();
    }

    /**
     * @return static
     */
    public static function today(): static
    {
        return new static('today');
    }

    /**
     * @return static
     */
    public static function yesterday(): static
    {
        return new static('yesterday');
    }

    /**
     * @return static
     */
    public static function tomorrow(): static
    {
        return new static('tomorrow');
    }

    /**
     * @return static
     */
    public function startOfDay(): static
    {
        return $this->setTime(0, 0);
    }

    /**
     * @return static
     */
    public function endOfDay(): static
    {
        return $this->setTime(23, 59, 59, 999999);
    }

    /**
     * @param DateTimeInterface|null $context
     * @return bool
     */
    public function isPast(DateTimeInterface $context = null): bool
    {
        return $this < ($context ?? static::now());
    }

    /**
     * @param DateTimeInterface|null $context
     * @return bool
     */
    public function isFuture(DateTimeInterface $context = null): bool
    {
        return $this > ($context ?? static::now());
    }

    # endregion Relative -----------------------------------------------------------------------------------------------

    # region Calendar --------------------------------------------------------------------------------------------------

    /**
     * @return int
     */
    public function daysInMonth(): int
    {
        return (int) $this->format('t');
    }

    /**
     * @return int
     */
    public function dayOfWeekNumber(): int
    {
        return (int) $this->format('N');
    }

    /**
     * @return int
     */
    public function dayOfYear(): int
    {
        return (int) $this->format('z');
    }

    /**
     * @return bool
     */
    public function isMonday(): bool
    {
        return $this->dayOfWeekNumber() === 1;
    }

    /**
     * @return bool
     */
    public function isTuesday(): bool
    {
        return $this->dayOfWeekNumber() === 2;
    }

    /**
     * @return bool
     */
    public function isWednesday(): bool
    {
        return $this->dayOfWeekNumber() === 3;
    }

    /**
     * @return bool
     */
    public function isThursday(): bool
    {
        return $this->dayOfWeekNumber() === 4;
    }

    /**
     * @return bool
     */
    public function isFriday(): bool
    {
        return $this->dayOfWeekNumber() === 5;
    }

    /**
     * @return bool
     */
    public function isSaturday(): bool
    {
        return $this->dayOfWeekNumber() === 6;
    }

    /**
     * @return bool
     */
    public function isSunday(): bool
    {
        return $this->dayOfWeekNumber() === 7;
    }

    /**
     * @return bool
     */
    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }

    /**
     * @return bool
     */
    public function isWeekend(): bool
    {
        return in_array($this->dayOfWeekNumber(), [6, 7]);
    }

    /**
     * @return bool
     */
    public function isLeapYear(): bool
    {
        return (bool) $this->format('L');
    }

    #endregion Calendar ------------------------------------------------------------------------------------------------

    # region Zone ------------------------------------------------------------------------------------------------------

    /**
     * @return static
     */
    public function utc(): static
    {
        return $this->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @return bool
     */
    public function isUtc(): bool
    {
        return $this->getOffset() === 0;
    }

    /**
     * @return bool
     */
    public function isDst(): bool
    {
        return (bool) $this->format('I');
    }

    /**
     * @return bool
     */
    public function isSummerTime(): bool
    {
        return $this->isDst();
    }

    # endregion Zone ---------------------------------------------------------------------------------------------------
}
