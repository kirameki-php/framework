<?php

namespace Kirameki\Database\Connection;

use Kirameki\Database\Query\Formatter;
use PDO;

class Connection
{
    public string $name;

    public array $config;

    public ?Formatter $formatter;

    public ?PDO $pdo;

    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function cursor(string $statement, array $bindings)
    {
        $pdo = $this->getPdo();
        $prepared = $pdo->prepare($statement);
        $prepared->execute($bindings);
        while($data = $prepared->fetch()) {
            yield $data;
        }
    }

    public function select(string $statement, array $bindings): array
    {
        $pdo = $this->getPdo();
        $prepared = $pdo->prepare($statement);
        $prepared->execute($bindings);
        return $prepared->fetchAll();
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo??= $this->connect();
    }

    public function getFormatter()
    {
        return $this->formatter ??= new Formatter($this);
    }
}
