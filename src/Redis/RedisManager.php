<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Closure;
use Kirameki\Core\Config;
use Kirameki\Event\EventManager;
use Kirameki\Redis\Adapters\Adapter;
use Kirameki\Redis\Adapters\PhpRedisAdapter;
use Kirameki\Support\Collection;
use LogicException;

class RedisManager
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var EventManager
     */
    protected EventManager $events;

    /**
     * @var array<Connection>
     */
    protected array $connections;

    /**
     * @var array<string, Closure>
     */
    protected array $adapters;

    /**
     * @var string
     */
    protected string $defaultAdapter = 'phpredis';

    /**
     * @param Config $config
     * @param EventManager $events
     */
    public function __construct(Config $config, EventManager $events)
    {
        $this->config = $config;
        $this->events = $events;
        $this->connections = [];
        $this->adapters = [];
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function using(string $name): Connection
    {
        return $this->connections[$name] ??= $this->createConnection($name, $this->getConfig($name));
    }

    /**
     * @param string $name
     * @return $this
     */
    public function purge(string $name): static
    {
        unset($this->connections[$name]);
        return $this;
    }

    /**
     * @param Connection $connection
     * @return $this
     */
    public function addConnection(Connection $connection): static
    {
        $this->connections[$connection->getName()] = $connection;
        return $this;
    }

    /**
     * @param string $name
     * @param callable(Config): Adapter $deferred
     * @return $this
     */
    public function addAdapter(string $name, callable $deferred): static
    {
        $this->adapters[$name] = $deferred(...);
        return $this;
    }

    /**
     * @param string $name
     * @param Config $config
     * @return Connection
     */
    protected function createConnection(string $name, Config $config): Connection
    {
        $adapterName = $config->getStringOrNull('adapter') ?? $this->defaultAdapter;
        $adapterResolver = $this->getAdapterResolver($adapterName);
        $adapter = $adapterResolver($config);
        return new Connection($name, $adapter, $this->events);
    }

    /**
     * @return Collection<string, Connection>
     */
    public function resolvedConnections(): Collection
    {
        return new Collection($this->connections);
    }

    /**
     * @param string $name
     * @return Config
     */
    public function getConfig(string $name): Config
    {
        return $this->config->for('connections.'.$name);
    }

    /**
     * @param string $name
     * @return Closure(Config): Adapter
     */
    protected function getAdapterResolver(string $name): Closure
    {
        if (!isset($this->adapters[$name])) {
            $this->addAdapter($name, $this->getDefaultAdapterResolver($name));
        }
        return $this->adapters[$name];
    }

    /**
     * @param string $adapter
     * @return Closure(Config): Adapter
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'phpredis' => static fn(Config $config) => new PhpRedisAdapter($config),
            default => throw new LogicException("Adapter: $adapter does not exist"),
        };
    }
}