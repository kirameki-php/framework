<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Result;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @var Result
     */
    public Result $result;

    /**
     * @var float
     */
    public float $elapsedMs;

    /**
     * @param Connection $connection
     * @param Result $result
     * @param float $elapsedMs
     */
    public function __construct(Connection $connection, Result $result, float $elapsedMs)
    {
        parent::__construct($connection);
        $this->result = $result;
        $this->elapsedMs = $elapsedMs;
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->result->getExecutedQuery();
    }
}
