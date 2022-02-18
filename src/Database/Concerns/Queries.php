<?php declare(strict_types=1);

namespace Kirameki\Database\Concerns;

use Generator;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
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
     * @param array<mixed>|null $bindings
     * @return array
     */
    public function query(string $statement, ?array $bindings = null): array
    {
        $then = microtime(true);
        $result = $this->adapter->query($statement, $bindings);
        $elapsedMs = (microtime(true) - $then) * 1000;
        $this->events->dispatchClass(QueryExecuted::class, $this, $statement, $bindings, $elapsedMs);
        return $result;
    }

    /**
     * @param string $statement
     * @param array<mixed>|null $bindings
     * @return int
     */
    public function affectingQuery(string $statement, ?array $bindings = null): int
    {
        $then = microtime(true);
        $result = $this->adapter->affectingQuery($statement, $bindings);
        $elapsedMs = (microtime(true) - $then) * 1000;
        $this->events->dispatchClass(QueryExecuted::class, $this, $statement, $bindings, $elapsedMs);
        return $result;
    }

    /**
     * @param string $statement
     * @param array<mixed>|null $bindings
     * @return Generator<mixed>
     */
    public function cursor(string $statement, ?array $bindings = []): Generator
    {
        $then = microtime(true);
        $result = $this->adapter->cursor($statement, $bindings);
        $elapsedMs = (microtime(true) - $then) * 1000;
        $this->events->dispatchClass(QueryExecuted::class, $this, $statement, $bindings, $elapsedMs);
        return $result;
    }
}
