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
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->deleteStatement($this->statement);
        $bindings = $formatter->deleteBindings($this->statement);
        return $this->connection->affectingQuery($statement, $bindings);
    }

    /**
     * @return array<string, string|array<mixed>>
     */
    public function inspect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->deleteStatement($this->statement);
        $bindings = $formatter->deleteBindings($this->statement);
        return compact('statement', 'bindings');
    }
}
