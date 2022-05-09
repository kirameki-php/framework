<?php declare(strict_types=1);

namespace Kirameki\Redis\Support;

use Iterator;
use Kirameki\Support\SequenceLazy;

/**
 * @extends SequenceLazy<int, string>
 */
class ScanResult extends SequenceLazy
{
    /**
     * @var array<int, string>|null
     */
    protected ?array $cached = null;

    /**
     * @inheritDoc
     */
    public function getIterator(): Iterator
    {
        if ($this->cached !== null) {
            yield from $this->cached;
            return;
        }

        $this->cached = [];
        foreach(parent::getIterator() as $index => $key) {
            yield $index => $key;
            $this->cached[$index] = $key;
        }
    }
}
