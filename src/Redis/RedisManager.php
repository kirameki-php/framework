<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Kirameki\Core\Config;
use Kirameki\Event\EventManager;
use Kirameki\Support\Collection;

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
     * @param Config $config
     * @param EventManager $events
     */
    public function __construct(Config $config, EventManager $events)
    {
        $this->config = $config;
        $this->events = $events;
        $this->connections = [];
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
     * @param Config $config
     * @return Connection
     */
    protected function createConnection(string $name, Config $config): Connection
    {
        return new Connection($name, $config, $this->events);
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
}
