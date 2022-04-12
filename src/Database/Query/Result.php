<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Support\Collection;

/**
 * @template-extends Collection<int, mixed>
 */
class Result extends Collection
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
    protected readonly int|Closure $affectedRowCount;

    /**
     * @param Adapter $adapter
     * @param string $statement
     * @param array<mixed> $bindings
     * @param array<int, mixed> $rows
     * @param int|Closure(): int $affectedRowCount
     */
    public function __construct(Adapter $adapter, string $statement, array $bindings, array $rows, int|Closure $affectedRowCount)
    {
        parent::__construct($rows);
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
