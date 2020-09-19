<?php

namespace Kirameki\Database\Connection;

use Generator;
use Kirameki\Database\Query\Formatter;
use PDO;
use PDOStatement;

class Connection
{
    protected string $name;

    protected array $config;

    protected ?Formatter $formatter;

    protected ?PDO $pdo;

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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getConfig(): array
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
        $prepared = $this->execQuery($statement, $bindings);
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
        return $this->execQuery($statement, $bindings)->fetchAll(PDO::FETCH_ASSOC);
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

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return PDOStatement
     */
    protected function execQuery(string $statement, ?array $bindings): PDOStatement
    {
        $pdo = $this->getPdo();
        $formatter = $this->getFormatter();
        $prepared = $pdo->prepare($statement);
        $prepared->execute($formatter->parameters($bindings ?? []));
        return $prepared;
    }
}
