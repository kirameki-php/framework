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
    protected array $drivers;

    /**
     *
     */
    public function __construct()
    {
        $this->connections = [];
        $this->drivers = [];
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
        $resolver = $this->getDriverResolver($config['driver']);
        return $this->connections[$name] = $resolver($name, $config);
    }

    /**
     * @param string $name
     * @param Closure $deferred
     * @return $this
     */
    public function addDriver(string $name, Closure $deferred)
    {
        $this->drivers[$name] = $deferred;
        return $this;
    }

    /**
     * @param string $driver
     * @return Closure
     */
    protected function getDriverResolver(string $driver): Closure
    {
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        switch ($driver) {
            case 'mysql': return fn(string $name, array $config) => new MySqlConnection($name, $config);
        }

        throw new RuntimeException('Undefined driver: '.$driver);
    }
}
