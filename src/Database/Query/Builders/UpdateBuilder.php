<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\UpdateStatement;

/**
 * @property UpdateStatement $statement
 */
class UpdateBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new UpdateStatement;
    }

    /**
     * @param array<string, mixed> $assignments
     * @return $this
     */
    public function set(array $assignments): static
    {
        $this->statement->data = $assignments;
        return $this;
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->getQueryFormatter()->formatUpdateStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->getBindingsForUpdate($this->statement);
    }

    /**
     * @return int
     */
    public function execute(): int
    {
        return $this->connection->affectingQuery($this->prepare(), $this->getBindings());
    }
}
