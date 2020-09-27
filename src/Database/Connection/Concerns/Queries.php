<?php

namespace Kirameki\Database\Connection\Concerns;

use Generator;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use PDO;
use PDOStatement;

/**
 * @mixin Connection
 */
trait Queries
{
    protected ?QueryFormatter $queryFormatter;

    /**
     * @param mixed ...$columns
     * @return SelectBuilder
     */
    public function select(...$columns)
    {
        return (new SelectBuilder($this))->columns($columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table)
    {
        return (new InsertBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table)
    {
        return (new UpdateBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table)
    {
        return (new DeleteBuilder($this))->table($table);
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
    public function affectingQuery(string $statement, ?array $bindings = null)
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
     * @return QueryFormatter
     */
    public function getQueryFormatter()
    {
        return $this->queryFormatter ??= new QueryFormatter($this);
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
