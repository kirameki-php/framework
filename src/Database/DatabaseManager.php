<?php

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Event\EventManager;
use RuntimeException;

class DatabaseManager
{
    /**
     * @var Connection[]
     */
    protected array $connections;

    /**
     * @var Closure[]
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
        if(isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = $this->getConfig($name);
        $resolver = $this->getAdapterResolver($config['adapter']);
        $adapter = $resolver($config);
        $connection = new Connection($name, $adapter, $this->events);
        return $this->connections[$name] = $connection;
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
     * @param Closure $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred): static
    {
        $this->adapters[$name] = $deferred;
        return $this;
    }

    /**
     * @return Connection[]
     */
    public function resolvedConnections(): array
    {
        return $this->connections;
    }

    /**
     * @param string $connection
     * @return array
     */
    protected function getConfig(string $connection): array
    {
        $config = config()->get('database.connections.'.$connection);

        if ($config === null) {
            throw new RuntimeException('Undefined database connection: '.$connection);
        }

        $config['connection'] = $connection;
        $config['database'] ??= $connection;

        return $config;
    }

    /**
     * @param string $name
     * @return Closure
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
     * @return Closure
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'mysql' => static fn(array $config) => new MySqlAdapter($config),
            'sqlite' => static fn(array $config) => new SqliteAdapter($config),
        };
    }
}
