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
    public function on(string $name): Connection
    {
        if(isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = config()->get('database.connections.'.$name);
        $config['connection'] = $name;

        $resolver = $this->getAdapterResolver($config['adapter']);
        $adapter = $resolver($config);
        return $this->connections[$name] = new Connection($config, $adapter);
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
