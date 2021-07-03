<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use PDO;
use function unlink;

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
    public function connect(): static
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
    public function disconnect(): static
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * @param bool $ifNotExist
     * @return void
     */
    public function createDatabase(bool $ifNotExist = true): void
    {
        // nothing necessary
    }

    /**
     * @param bool $ifNotExist
     */
    public function dropDatabase(bool $ifNotExist = true): void
    {
        if ($ifNotExist && !$this->databaseExists()) {
            return;
        }
        unlink($this->config['path']);
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
        $this->execute('DELETE FROM '.$table);
    }

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool
    {
        return true;
    }
}
