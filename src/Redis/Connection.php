<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Kirameki\Event\EventManager;
use Kirameki\Redis\Adapters\Adapter;
use Kirameki\Redis\Events\CommandExecuted;

class Connection
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Adapter
     */
    protected Adapter $adapter;

    /**
     * @var EventManager
     */
    protected EventManager $event;

    public function __construct(string $name, Adapter $adapter, EventManager $event)
    {
        $this->name = $name;
        $this->adapter = $adapter;
        $this->event = $event;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * @param string $name
     * @param array<mixed> $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->command($name, $args);
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    protected function command(string $name, mixed ...$args): mixed
    {
        $then = hrtime(true);

        $result = ($this->getAdapter()->$name)($name, ...$args);

        $timeMs = (hrtime(true) - $then) * 1_000_000;

        $this->event->dispatchClass(CommandExecuted::class, $this, $name, $args, $result, $timeMs);

        return $result;
    }
}
