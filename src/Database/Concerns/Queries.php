<?php declare(strict_types=1);

namespace Kirameki\Database\Concerns;

use Generator;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Query\ResultLazy;

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
     * @param array<mixed> $bindings
     * @return Result
     */
    public function query(string $statement, array $bindings = []): Result
    {
        $execution = $this->adapter->query($statement, $bindings);
        $result = new Result($this, $execution);
        $this->events->dispatchClass(QueryExecuted::class, $this, $result);
        return $result;
    }

    /**
     * @param string $statement
     * @param array<mixed> $bindings
     * @return ResultLazy
     */
    public function cursor(string $statement, array $bindings = []): ResultLazy
    {
        $execution = $this->adapter->cursor($statement, $bindings);
        $result = new ResultLazy($this, $execution);
        $this->events->dispatchClass(QueryExecuted::class, $this, $result);
        return $result;
    }
}
