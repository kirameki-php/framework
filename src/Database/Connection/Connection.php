<?php

namespace Kirameki\Database\Connection;

use Generator;
use Kirameki\Database\Query\Builder;
use Kirameki\Database\Query\Formatter;
use PDO;
use PDOStatement;

class Connection
{
    use Concerns\Queries;

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
            $this->reconnect();
        }
        return $this->pdo;
    }

    /**
     * @return $this
     */
    public function reconnect()
    {
        $config = $this->getConfig();
        if (isset($config['socket'])) {
            $hostOrSocket = 'unix_socket='.$config['socket'];
        } else {
            $hostOrSocket = 'host='.$config['host'];
            $hostOrSocket.= isset($config['port']) ? 'port='.$config['port'] : '';
        }
        $database = isset($config['database']) ? 'dbname='.$config['database'] : '';
        $charset = isset($config['charset']) ? 'charset='.$config['charset'] : '';
        $dsn = "mysql:{$hostOrSocket}{$database}{$charset}";
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? null;
        $options = $config['options'] ?? [];
        $options+= [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_FOUND_ROWS => TRUE,
        ];
        $this->pdo = new PDO($dsn, $username, $password, $options);
        return $this;
    }

    /**
     * @return bool
     */
    public function close()
    {
        $this->pdo = null;
        return true;
    }
}
