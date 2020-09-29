<?php

namespace Kirameki\Database\Connection\Drivers;

use PDO;

class SqliteDriver extends PdoDriver
{
    /**
     * @return $this
     */
    public function connect()
    {
        $config = $this->getConfig();
        $path = $config['path'] ?? app()->getStoragePath($config['connection'].'.db');
        $dsn = 'sqlite:'.$path;
        $options = $config['options'] ?? [];
        $options+= [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $this->pdo = new PDO($dsn, null, null, $options);
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
