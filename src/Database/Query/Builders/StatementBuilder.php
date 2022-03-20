<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Query\Statements\BaseStatement;
use Kirameki\Support\Concerns\Macroable;
use Kirameki\Support\Concerns\Tappable;

abstract class StatementBuilder
{
    use Macroable;
    use Tappable;

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
     * @return Result
     */
    protected function execute(): Result
    {
        return $this->connection->query($this->prepare(), $this->getBindings());
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
