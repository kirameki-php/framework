<?php

namespace Kirameki\Database\Connection;

use PDO;

class SqliteConnection extends Connection
{
    /**
     * @return $this
     */
    public function connect()
    {
        $config = $this->getConfig();
        $path = $config['path'] ?? app()->getBasePath('storage/'.$this->getName().'.db');
        $dsn = "sqlite:{$path}";
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
