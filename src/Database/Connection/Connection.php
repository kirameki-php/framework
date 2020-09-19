<?php

namespace Kirameki\Database\Connection;

use Generator;
use Kirameki\Database\Query\Formatter;
use PDO;

class Connection
{
    public string $name;

    public array $config;

    public ?Formatter $formatter;

    public ?PDO $pdo;

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
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return Generator
     */
    public function cursor(string $statement, array $bindings): Generator
    {
        $pdo = $this->getPdo();
        $prepared = $pdo->prepare($statement);
        $prepared->execute($bindings);
        while($data = $prepared->fetch()) {
            yield $data;
        }
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return array
     */
    public function query(string $statement, ?array $bindings = null): array
    {
        $pdo = $this->getPdo();
        $prepared = $pdo->prepare($statement);
        $prepared->execute($bindings);
        return $prepared->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo??= $this->connect();
    }

    /**
     * @return Formatter
     */
    public function getFormatter()
    {
        return $this->formatter ??= new Formatter($this);
    }
}
