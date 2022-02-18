<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\AdapterInterface;
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
        return $this->connections[$name] ??= $this->createConnection($this->getConfig($name));
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
     * @param callable(array<string, scalar>): AdapterInterface $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred): static
    {
        $this->adapters[$name] = $deferred;
        return $this;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $config
     * @return Connection
     */
    protected function createConnection(array $config): Connection
    {
        $adapterResolver = $this->getAdapterResolver($config['adapter']);
        $adapter = $adapterResolver($config);
        return new Connection($adapter, $this->events);
    }

    /**
     * @return Connection[]
     */
    public function resolvedConnections(): array
    {
        return $this->connections;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getConfig(string $name): array
    {
        $config = config()->get('database.connections.'.$name);

        if ($config === null) {
            throw new RuntimeException('Undefined database connection: '.$name);
        }

        $config['connection'] = $name;
        $config['database'] ??= $name;

        return $config;
    }

    /**
     * @param string $name
     * @return Closure(array<string, scalar>): AdapterInterface
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
     * @return Closure(array<string, scalar>): AdapterInterface
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'mysql' => static fn(array $config) => new MySqlAdapter($config),
            'sqlite' => static fn(array $config) => new SqliteAdapter($config),
        };
    }
}
