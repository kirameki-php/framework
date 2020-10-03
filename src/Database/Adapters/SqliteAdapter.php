<?php

namespace Kirameki\Database\Adapters;

use PDO;

class SqliteAdapter extends PdoAdapter
{
    public function __construct(array $config)
    {
        $config['path'] ??= app()->getStoragePath($config['connection'].'.db');
        parent::__construct($config);
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $config = $this->getConfig();
        $dsn = 'sqlite:'.$config['path'];
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

    /**
     * @return void
     */
    public function createDatabase(): void
    {
        $this->config['path'];
    }

    /**
     * @return void
     */
    public function dropDatabase(): void
    {
        if ($this->databaseExists()) {
            unlink($this->config['path']);
        }
    }

    /**
     * @return bool
     */
    public function databaseExists(): bool
    {
        return file_exists($this->config['path']);
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->executeSchema('DELETE FROM '.$table);
    }
}
