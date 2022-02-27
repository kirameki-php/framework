<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Statements\BaseStatement;

abstract class StatementBuilder
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var BaseStatement
     */
    protected BaseStatement $statement;

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @param string $table
     * @param string|null $as
     * @return $this
     */
    public function table(string $table, ?string $as = null): static
    {
        $this->statement->table = $table;
        $this->statement->tableAlias = $as;
        return $this;
    }

    /**
     * @return static
     */
    protected function copy(): static
    {
        return clone $this;
    }

    /**
     * @return string
     */
    abstract public function prepare(): string;

    /**
     * @return array<mixed>
     */
    abstract public function getBindings(): array;

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getStatement(): BaseStatement
    {
        return $this->statement;
    }

    /**
     * @return Formatter
     */
    protected function getQueryFormatter(): Formatter
    {
        return $this->connection->getQueryFormatter();
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->getQueryFormatter()->interpolate($this->prepare(), $this->getBindings());
    }
}
