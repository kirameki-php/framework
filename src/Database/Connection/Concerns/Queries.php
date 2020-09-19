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
    public function execSelect(string $statement, ?array $bindings = null): array
    {
        return $this->execQuery($statement, $bindings)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return int
     */
    public function execAffecting(string $statement, ?array $bindings = null)
    {
        return $this->execQuery($statement, $bindings)->rowCount();
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
        $prepared = $this->execQuery($statement, $bindings);
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
    protected function execQuery(string $statement, ?array $bindings): PDOStatement
    {
        $prepared = $this->prepare($statement);
        $prepared->execute($this->prepareBindings($bindings ?? []));
        return $prepared;
    }

    /**
     * @param string $statement
     * @return PDOStatement
     */
    protected function prepare(string $statement): PDOStatement
    {
        return $this->getPdo()->prepare($statement);
    }

    /**
     * @param array $bindings
     * @return array
     */
    protected function prepareBindings(array $bindings)
    {
        $formatter = $this->getQueryFormatter();
        $prepared = [];
        foreach($bindings as $name => $binding) {
            $prepared[$name] = $formatter->parameter($binding);
        }
        return $prepared;
    }
}
