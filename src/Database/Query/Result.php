<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Support\Collection;

/**
 * @template-extends Collection<int, mixed>
 */
class Result extends Collection
{
    /**
     * @var int|Closure(): int
     */
    protected int|Closure $affectedRowCount;

    /**
     * @param array<int, mixed> $items
     * @param int|Closure(): int $affectedRowCount
     */
    public function __construct(array $items, int|Closure $affectedRowCount)
    {
        parent::__construct($items);
        $this->affectedRowCount = $affectedRowCount;
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
