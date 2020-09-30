<?php

namespace Kirameki\Database;

use Generator;
use Kirameki\Database\Adapters\AdapterInterface;
use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

class Connection
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var AdapterInterface
     */
    protected AdapterInterface $adapter;

    /**
     * @var QueryFormatter|null
     */
    protected ?QueryFormatter $queryFormatter;

    /**
     * @var SchemaFormatter|null
     */
    protected ?SchemaFormatter $schemaFormatter;

    /**
     * @param string $name
     * @param AdapterInterface $adapter
     */
    public function __construct(string $name, AdapterInterface $adapter)
    {
        $this->name = $name;
        $this->adapter = $adapter;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @return $this
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $this->adapter->connect();
        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect()
    {
        $this->adapter->disconnect();
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->adapter->isConnected();
    }

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter
    {
        return $this->queryFormatter ??= $this->adapter->getQueryFormatter();
    }

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return $this->schemaFormatter ??= $this->adapter->getSchemaFormatter();
    }

    /**
     * @param mixed ...$columns
     * @return SelectBuilder
     */
    public function select(...$columns): SelectBuilder
    {
        return (new SelectBuilder($this))->columns($columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return (new InsertBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return (new UpdateBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
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
        return $this->adapter->query($statement, $bindings);
    }

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return int
     */
    public function affectingQuery(string $statement, ?array $bindings = null): int
    {
        return $this->adapter->affectingQuery($statement, $bindings);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return Generator
     */
    public function cursor(string $statement, array $bindings): Generator
    {
        return $this->adapter->cursor($statement, $bindings);
    }

    /**
     * @param string $statement
     */
    public function execute(string $statement): void
    {
        $this->adapter->execute($statement);
    }
}
