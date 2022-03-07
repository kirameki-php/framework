<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\DeleteStatement;

/**
 * @property DeleteStatement $statement;
 */
class DeleteBuilder extends ConditionsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new DeleteStatement;
    }

    /**
     * @return int
     */
    public function execute(): int
    {
        return $this->connection->affectingQuery($this->prepare(), $this->getBindings());
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->getQueryFormatter()->formatDelete($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->getBindingsForDelete($this->statement);
    }
}
