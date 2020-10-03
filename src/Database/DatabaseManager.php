<?php

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
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
     *
     */
    public function __construct()
    {
        $this->connections = [];
        $this->adapters = [];
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

        return $this->connections[$name] = new Connection($name, $adapter);
    }

    /**
     * @param string $name
     * @param Closure $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred)
    {
        $this->adapters[$name] = $deferred;
        return $this;
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
        switch ($adapter) {
            case 'mysql' : return static fn(array $config) => new MySqlAdapter($config);
            case 'sqlite': return static fn(array $config) => new SqliteAdapter($config);
        }
        throw new RuntimeException('Undefined adapter: '.$adapter);
    }
}
