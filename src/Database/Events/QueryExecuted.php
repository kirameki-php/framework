<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @var string
     */
    public string $statement;

    /**
     * @var list<mixed>
     */
    public array $bindings;

    /**
     * @var float
     */
    public float $elapsedMs;

    /**
     * @param Connection $connection
     * @param string $statement
     * @param list<mixed> $bindings
     * @param float $elapsedMs
     */
    public function __construct(Connection $connection, string $statement, array $bindings, float $elapsedMs)
    {
        parent::__construct($connection);
        $this->statement = $statement;
        $this->bindings = $bindings;
        $this->elapsedMs = $elapsedMs;
    }

    /**
     * @return string
     */
    public function getExecutedQuery(): string
    {
        return $this->connection->getQueryFormatter()->interpolate($this->statement, $this->bindings);
    }
}
