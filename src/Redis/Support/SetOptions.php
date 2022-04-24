<?php declare(strict_types=1);

namespace Kirameki\Redis\Support;

use DateTimeInterface;

class SetOptions
{
    /**
     * @var float
     */
    protected float $expireIn;

    /**
     * @var float
     */
    protected float $expireAt;

    /**
     * @var bool
     */
    protected bool $keepTtl;

    /**
     * @var bool
     */
    protected bool $returnOldString;

    /**
     * @param float $seconds
     * @return $this
     */
    public function expireIn(float $seconds): static
    {
        $this->expireIn = $seconds;
        return $this;
    }

    /**
     * @param float|DateTimeInterface $time
     * @return $this
     */
    public function expireAt(float|DateTimeInterface $time): static
    {
        $this->expireAt = ($time instanceof DateTimeInterface)
            ? (float) $time->format('U.u')
            : $time;
        return $this;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function keepTtl(bool $toggle = true): static
    {
        $this->keepTtl = $toggle;
        return $this;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function returnOldString(bool $toggle = true): static
    {
        $this->returnOldString = $toggle;
        return $this;
    }

    /**
     * @return array<int|string, scalar>
     */
    public function toArray(): array
    {
        $options = [];

        if ($this->expireIn) {
            $options['PX'] = $this->expireIn * 1000;
        }

        if ($this->expireAt) {
            $options['PXAT'] = $this->expireAt * 1000;
        }

        if ($this->keepTtl) {
            $options[] = 'KEEPTTL';
        }

        if ($this->returnOldString) {
            $options[] = 'GET';
        }

        return $options;
    }
}
