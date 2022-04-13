<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Iterator;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Support\SequenceLazy;

/**
 * @extends SequenceLazy<int, mixed>
 */
class ResultLazy extends SequenceLazy
{
    /**
     * @var Adapter
     */
    protected readonly Adapter $adapter;

    /**
     * @var string
     */
    protected readonly string $statement;

    /**
     * @var array<mixed>
     */
    protected readonly array $bindings;

    /**
     * @var int|Closure(): int
     */
    protected int|Closure $affectedRowCount;

    /**
     * @param Adapter $adapter
     * @param string $statement
     * @param array<mixed> $bindings
     * @param Closure(): Iterator<mixed> $iterator
     * @param int|Closure(): int $affectedRowCount
     */
    public function __construct(Adapter $adapter, string $statement, array $bindings, Closure $iterator, int|Closure $affectedRowCount)
    {
        parent::__construct($iterator);
        $this->adapter = $adapter;
        $this->statement = $statement;
        $this->bindings = $bindings;
        $this->affectedRowCount = $affectedRowCount;
    }

    /**
     * @return string
     */
    public function getExecutedQuery(): string
    {
        return $this->adapter->getQueryFormatter()->interpolate($this->statement, $this->bindings);
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * @return string
     */
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @return int
     */
    public function getAffectedRowCount(): int
    {
        if ($this->affectedRowCount instanceof Closure) {
            $closure = $this->affectedRowCount;
            $this->affectedRowCount = $closure();
        }
        return $this->affectedRowCount;
    }
}
