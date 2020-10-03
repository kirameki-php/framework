<?php

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Query\Formatters\MySqlFormatter as MySqlQueryFormatter;
use PDO;

class MySqlAdapter extends PdoAdapter
{
    /**
     * @return $this
     */
    public function connect()
    {
        $config = $this->config;
        if (isset($config['socket'])) {
            $hostOrSocket = 'unix_socket='.$config['socket'];
        } else {
            $hostOrSocket = 'host='.$config['host'];
            $hostOrSocket.= isset($config['port']) ? 'port='.$config['port'] : '';
        }
        $database = isset($config['database']) ? 'dbname='.$config['database'] : '';
        $charset = isset($config['charset']) ? 'charset='.$config['charset'] : '';
        $dsn = "mysql:{$hostOrSocket};{$database};{$charset}";
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
     * @return $this
     */
    public function disconnect()
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * @return MySqlQueryFormatter
     */
    public function getQueryFormatter(): MySqlQueryFormatter
    {
        return new MySqlQueryFormatter();
    }

    /**
     * @return void
     */
    public function createDatabase(): void
    {
        $this->executeSchema('CREATE DATABASE '.$this->config['database']);
    }

    /**
     * @return void
     */
    public function dropDatabase(): void
    {
        $this->executeSchema('CREATE DATABASE '.$this->config['database']);
    }

    /**
     * @return bool
     */
    public function databaseExists(): bool
    {
        return 'SHOW DATABASES LIKE '.str_replace("'", "''", $this->config['database']);
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->executeSchema('TRUNCATE TABLE '.$table);
    }
}
