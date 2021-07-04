<?php declare(strict_types=1);

namespace Kirameki\Container;

use Closure;
use function call_user_func_array;

class ClosureEntry implements EntryInterface
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var Closure
     */
    protected Closure $resolver;

    /**
     * @var array
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
     * @var mixed
     */
    protected mixed $instance;

    /**
     * @param string $id
     * @param Closure $resolver
     * @param array $arguments
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
     * @return mixed
     */
    public function getInstance(): mixed
    {
        if (!$this->cacheable) {
            $instance = call_user_func_array($this->resolver, $this->arguments);
            return $instance;
        }

        if (!$this->resolved) {
            $this->resolved = true;
            $this->instance = call_user_func_array($this->resolver, $this->arguments);
        }

        return $this->instance;
    }

    /**
     * @return bool
     */
    public function cached(): bool
    {
        return $this->resolved;
    }
}
