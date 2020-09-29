<?php

namespace Kirameki\Database\Connection\Drivers;

use PDO;

class MySqlDriver extends PdoDriver
{
    /**
     * @return $this
     */
    public function connect()
    {
        $config = $this->getConfig();
        if (isset($config['socket'])) {
            $hostOrSocket = 'unix_socket='.$config['socket'];
        } else {
            $hostOrSocket = 'host='.$config['host'];
            $hostOrSocket.= isset($config['port']) ? 'port='.$config['port'] : '';
        }
        $database = 'dbname='.($config['database'] ?? $config['connection']);
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
     * @return $this
     */
    public function disconnect()
    {
        $this->pdo = null;
        return $this;
    }
}