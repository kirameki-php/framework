<?php declare(strict_types=1);

namespace Kirameki\Container;

use Closure;
use Webmozart\Assert\Assert;
use function call_user_func_array;

/**
 * @template TEntry
 * @template-implements Entry<TEntry>
 */
class ClosureEntry implements Entry
{
    /**
     * @var class-string
     */
    protected string $id;

    /**
     * @var Closure
     */
    protected Closure $resolver;

    /**
     * @var array<mixed>
     */
    protected array $arguments;

    /**
     * @var bool
     */
    protected bool $cacheable;

    /**
     * @var bool
     */
    protected bool $resolved;

    /**
     * @var TEntry|null
     */
    protected mixed $instance;

    /**
     * @param class-string<TEntry> $id
     * @param Closure $resolver
     * @param array<mixed> $arguments
     * @param bool $cacheable
     */
    public function __construct(string $id, Closure $resolver, array $arguments, bool $cacheable)
    {
        $this->id = $id;
        $this->resolver = $resolver;
        $this->arguments = $arguments;
        $this->cacheable = $cacheable;
        $this->resolved = false;
        $this->instance = null;
    }

    /**
     * @return string
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
        if (!$this->cacheable) {
            return call_user_func_array($this->resolver, $this->arguments); /** @phpstan-ignore-line */
        }

        if (!$this->resolved) {
            $this->resolved = true;
            $this->instance = call_user_func_array($this->resolver, $this->arguments); /** @phpstan-ignore-line */
        }

        return $this->instance ?? Assert::notNull($this->instance);
    }

    /**
     * @return bool
     */
    public function cached(): bool
    {
        return $this->resolved;
    }
}
