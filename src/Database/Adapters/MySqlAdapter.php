<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Formatters\MySqlFormatter as MySqlQueryFormatter;
use PDO;
use function array_filter;
use function implode;

class MySqlAdapter extends PdoAdapter
{
    /**
     * @return $this
     */
    public function connect(): static
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
    public function disconnect(): static
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
        $copy->execute(implode(' ', array_filter([
            'CREATE DATABASE',
            $ifNotExist ? 'IF NOT EXISTS' : null,
            $this->config['database'],
        ])));
    }

    /**
     * @param bool $ifNotExist
     */
    public function dropDatabase(bool $ifNotExist = true): void
    {
        $copy = (clone $this);
        $copy->config['database'] = null;
        $copy->execute(implode(' ', array_filter([
            'DROP DATABASE',
            $ifNotExist ? 'IF EXISTS' : null,
            $this->config['database'],
        ])));
    }

    /**
     * @return bool
     */
    public function databaseExists(): bool
    {
        return !empty($this->query("SHOW DATABASES LIKE '".$this->config['database']."'"));
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->execute('TRUNCATE TABLE '.$table);
    }

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
