<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Core\Config;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Event\EventManager;
use Kirameki\Support\Collection;
use RuntimeException;

class DatabaseManager
{
    /**
     * @var array<Connection>
     */
    protected array $connections;

    /**
     * @var array<string, Closure>
     */
    protected array $adapters;

    /**
     * @var EventManager
     */
    protected EventManager $events;

    /**
     * @param EventManager $events
     */
    public function __construct(EventManager $events)
    {
        $this->connections = [];
        $this->adapters = [];
        $this->events = $events;
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
     * @return $this
     */
    public function purgeAll(): static
    {
        $this->connections = [];
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
        $adapterResolver = $this->getAdapterResolver($config->getString('adapter'));
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
        $config = config()->for('database.connections.'.$name);

        $config['connection'] = $name;
        $config['database'] ??= $name;

        return $config;
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
            'mysql' => static fn(Config $config) => new MySqlAdapter($config),
            'sqlite' => static fn(Config $config) => new SqliteAdapter($config),
            default => throw new RuntimeException("Adapter: $adapter does not exist"),
        };
    }
}
