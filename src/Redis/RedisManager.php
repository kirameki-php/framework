<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Closure;
use Kirameki\Core\Config;
use Kirameki\Event\EventManager;
use Kirameki\Redis\Adapters\Adapter;
use Kirameki\Redis\Adapters\RedisAdapter;
use Kirameki\Support\Collection;
use LogicException;
use function array_key_exists;

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
     * @var array<string, Connection>
     */
    protected array $connections;

    /**
     * @var array<string, Closure>
     */
    protected array $adapters;

    /**
     * @var string
     */
    protected string $defaultAdapter = 'redis';

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
        return $this->connections[$name] ??= $this->createConnection($name);
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
     * @param Closure(Config): Adapter $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred): static
    {
        $this->adapters[$name] = $deferred(...);
        return $this;
    }

    /**
     * @param string $name
     * @return Connection
     */
    protected function createConnection(string $name): Connection
    {
        $config = $this->getConfig($name);
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
     * @param string $adapter
     * @return Closure(Config): Adapter
     */
    protected function getAdapterResolver(string $adapter): Closure
    {
        if (!array_key_exists($adapter, $this->adapters)) {
            $this->addAdapter($adapter, $this->getDefaultAdapterResolver($adapter));
        }
        return $this->adapters[$adapter];
    }

    /**
     * @param string $name
     * @return Closure(Config): Adapter
     */
    protected function getDefaultAdapterResolver(string $name): Closure
    {
        return match ($name) {
            'redis' => static fn(Config $config) => new RedisAdapter($config),
            'redis-cluster' => static fn(Config $config) => new RedisAdapter($config),
            default => throw new LogicException("Adapter: $name does not exist"),
        };
    }
}
