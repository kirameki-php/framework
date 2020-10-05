<?php

namespace Kirameki\Database\Concerns;

use Generator;
use Kirameki\Database\Connection;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;

/**
 * @mixin Connection
 */
trait Queries
{
    /**
     * @var QueryFormatter|null
     */
    protected ?QueryFormatter $queryFormatter;

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter
    {
        return $this->queryFormatter ??= $this->adapter->getQueryFormatter();
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
}
