<?php

namespace Kirameki\Database;

use Closure;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Connection\MySqlConnection;
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
        $resolver = $this->getAdapterResolver($config['adapter']);
        return $this->connections[$name] = $resolver($name, $config);
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
        if (isset($this->adapters[$adapter])) {
            return $this->adapters[$adapter];
        }

        switch ($adapter) {
            case 'mysql': return fn(string $name, array $config) => new MySqlConnection($name, $config);
        }

        throw new RuntimeException('Undefined adapter: '.$adapter);
    }
}
