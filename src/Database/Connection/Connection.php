<?php

namespace Kirameki\Database\Connection;

use Kirameki\Database\Connection\Drivers\Driver;
use PDO;

abstract class Connection
{
    use Concerns\Queries,
        Concerns\Schemas;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Driver
     */
    protected Driver $driver;

    /**
     * @param string $name
     * @param Driver $driver
     */
    public function __construct(string $name, Driver $driver)
    {
        $this->name = $name;
        $this->driver = $driver;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->driver->getConfig();
    }

    /**
     * @return $this
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $this->driver->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect()
    {
        $this->driver->disconnect();
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->driver->isConnected();
    }
}
