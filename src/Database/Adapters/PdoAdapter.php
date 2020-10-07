<?php

namespace Kirameki\Database\Adapters;

use Generator;
use Kirameki\Database\Connection;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * @mixin Connection
 */
abstract class PdoAdapter implements AdapterInterface
{
    /**
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * @var array
     */
    protected array $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

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
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @param string $id
     */
    public function setSavepoint(string $id): void
    {
        $this->getPdo()->exec('SAVEPOINT '.$this->alphanumeric($id));
    }

    /**
     * @param string $id
     */
    public function rollbackSavepoint(string $id): void
    {
        $this->getPdo()->exec('ROLLBACK TO SAVEPOINT '.$this->alphanumeric($id));
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @param string $statement
     */
    public function execute(string $statement): void
    {
        $this->getPdo()->exec($statement);
    }

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter
    {
        return new QueryFormatter();
    }

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return new SchemaFormatter();
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return PDOStatement
     */
    protected function execQuery(string $statement, ?array $bindings): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement);
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

    /**
     * @param string $str
     * @return string
     */
    protected function alphanumeric(string $str): string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $str)) {
            throw new RuntimeException('Invalid string: "'.$str.'". Only alphanumeric characters, "_", and "-" are allowed.');
        }
        return $str;
    }
}
