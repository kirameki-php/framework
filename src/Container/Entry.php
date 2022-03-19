<?php declare(strict_types=1);

namespace Kirameki\Container;

/**
 * @template TEntry
 */
interface Entry
{
    /**
     * @return class-string<TEntry>
     */
    public function getId(): string;

    /**
     * @return TEntry
     */
    public function getInstance(): mixed;
}
