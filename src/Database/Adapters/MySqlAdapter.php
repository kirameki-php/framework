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
        $parts = [];
        if (isset($config['socket'])) {
            $parts[] = 'unix_socket='.$config['socket'];
        } else {
            $host = 'host='.$config['host'];
            $host.= isset($config['port']) ? 'port='.$config['port'] : '';
            $parts[] = $host;
        }
        if (isset($config['database'])) {
            $parts[] = 'dbname='.$config['database'];
        }
        if (isset($config['charset'])) {
            $parts[] = 'charset='.$config['charset'];
        }
        $dsn = 'mysql:'.implode(';', $parts);
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
     * @param bool $ifNotExist
     */
    public function createDatabase(bool $ifNotExist = true): void
    {
        $copy = (clone $this);
        $copy->config['database'] = null;
        $copy->executeSchema(implode(' ', array_filter([
            'CREATE DATABASE',
            $ifNotExist ? 'IF NOT EXISTS ' : null,
            $this->config['database'],
        ])));
    }

    /**
     * @param bool $ifNotExist
     */
    public function dropDatabase(bool $ifNotExist = true): void
    {
        $this->executeSchema(implode(' ', array_filter([
            'DROP DATABASE',
            $ifNotExist ? 'IF NOT EXISTS ' : null,
            $this->config['database'],
        ])));
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
