<?php declare(strict_types=1);

namespace Kirameki\Support;

final class Miss
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
        return self::$instance ??= new self();
    }
}
