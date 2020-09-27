<?php

namespace Kirameki\Database\Connection;

use PDO;

abstract class Connection
{
    use Concerns\Queries,
        Concerns\Schemas;

    protected string $name;

    protected array $config;

    protected ?PDO $pdo;

    /**
     * @param string $name
     * @param array $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
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
        return $this->config;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
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
    abstract public function connect();


    /**
     * @return $this
     */
    abstract public function disconnect();
}
