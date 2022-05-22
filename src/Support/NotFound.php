<?php declare(strict_types=1);

namespace Kirameki\Support;

final class NotFound
{
    /**
     * @var self|null
     */
    protected static ?self $instance = null;

    /**
     * @return self
     */
    public static function instance(): self
    {
        return static::$instance ??= new self();
    }
}
