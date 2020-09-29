<?php

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Connection\Adapters\MySqlAdapter;
use Kirameki\Database\Connection\Adapters\SqliteAdapter;
use Kirameki\Database\Connection\Connection;
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
    public function on(string $name): Connection
    {
        if(isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = config()->get('database.connections.'.$name);
        $config['connection'] = $name;

        $resolver = $this->getAdapterResolver($config['adapter']);
        $adapter = $resolver($config);
        return $this->connections[$name] = new Connection($name);
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
     * @param string $adapter
     * @return Closure
     */
    protected function getAdapterResolver(string $adapter): Closure
    {
        if (!isset($this->adapters[$adapter])) {
            $this->addAdapter($adapter, $this->getDefaultAdapterResolver($adapter));
        }
        return $this->adapters[$adapter];
    }

    /**
     * @param string $adapter
     * @return Closure
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        switch ($adapter) {
            case 'mysql' : return fn(array $config) => new MySqlAdapter($config);
            case 'sqlite': return fn(array $config) => new SqliteAdapter($config);
        }
        throw new RuntimeException('Undefined adapter: '.$adapter);
    }
}
