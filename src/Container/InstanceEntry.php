<?php declare(strict_types=1);

namespace Kirameki\Container;

/**
 * @template TEntry
 * @template-implements EntryInterface<TEntry>
 */
class InstanceEntry implements EntryInterface
{
    /**
     * @var class-string<TEntry>
     */
    protected string $id;

    /**
     * @var TEntry
     */
    protected mixed $instance;

    /**
     * @param class-string<TEntry> $id
     * @param TEntry $instance
     */
    public function __construct(string $id, mixed $instance)
    {
        $this->id = $id;
        $this->instance = $instance;
    }

    /**
     * @return class-string<TEntry>
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return TEntry
     */
    public function getInstance(): mixed
    {
        return $this->instance;
    }
}
