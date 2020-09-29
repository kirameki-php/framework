<?php

namespace Kirameki\Database\Connection\Drivers;

use Generator;
use Kirameki\Database\Connection\Connection;
use PDO;
use PDOStatement;

/**
 * @mixin Connection
 */
abstract class PdoDriver extends Driver
{
    /**
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return array
     */
    public function query(string $statement, ?array $bindings = null): array
    {
        return $this->execQuery($statement, $bindings)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return int
     */
    public function affectingQuery(string $statement, ?array $bindings = null): int
    {
        return $this->execQuery($statement, $bindings)->rowCount();
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return Generator
     */
    public function cursor(string $statement, array $bindings): Generator
    {
        $prepared = $this->execQuery($statement, $bindings);
        while ($data = $prepared->fetch()) {
            yield $data;
        }
    }

    /**
     * @param string $statement
     */
    public function execute(string $statement): void
    {
        $this->getPdo()->exec($statement);
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return PDOStatement
     */
    protected function execQuery(string $statement, ?array $bindings): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement)($statement);
        $prepared->execute($this->prepareBindings($bindings ?? []));
        return $prepared;
    }

    /**
     * @param array $bindings
     * @return array
     */
    protected function prepareBindings(array $bindings): array
    {
        $formatter = $this->getQueryFormatter();
        $prepared = [];
        foreach($bindings as $name => $binding) {
            $prepared[$name] = $formatter->parameter($binding);
        }
        return $prepared;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }
}
