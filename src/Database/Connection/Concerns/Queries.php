<?php

namespace Kirameki\Database\Connection\Concerns;

use Generator;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Builder;
use Kirameki\Database\Query\Formatter;
use PDO;
use PDOStatement;

/**
 * @mixin Connection
 */
trait Queries
{
    protected ?Formatter $formatter;

    /**
     * @return Builder
     */
    public function query()
    {
        return new Builder($this);
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return array
     */
    public function execute(string $statement, ?array $bindings = null): array
    {
        return $this->runQuery($statement, $bindings)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $statement
     * @return array
     */
    public function unprepared(string $statement): array
    {
        return $this->getPdo()->query($statement)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return Generator
     */
    public function cursor(string $statement, array $bindings): Generator
    {
        $prepared = $this->runQuery($statement, $bindings);
        while ($data = $prepared->fetch()) {
            yield $data;
        }
    }

    /**
     * @return Formatter
     */
    public function getQueryFormatter()
    {
        return $this->formatter ??= new Formatter($this);
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return PDOStatement
     */
    protected function runQuery(string $statement, ?array $bindings): PDOStatement
    {
        $pdo = $this->getPdo();
        $formatter = $this->getQueryFormatter();
        $prepared = $pdo->prepare($statement);
        $prepared->execute($formatter->parameters($bindings ?? []));
        return $prepared;
    }
}
