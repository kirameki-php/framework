<?php declare(strict_types=1);

namespace Kirameki\Container;

use Closure;

class InstanceEntry implements EntryInterface
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var mixed
     */
    protected mixed $instance;

    /**
     * @param string $id
     * @param mixed $instance
     */
    public function __construct(string $id, mixed $instance)
    {
        $this->id = $id;
        $this->instance = $instance;
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
        return $this->instance;
    }
}
