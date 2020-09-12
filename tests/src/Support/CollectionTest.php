<?php

namespace Kirameki\Tests\Support;

use ErrorException;
use Kirameki\Support\Collection;
use Kirameki\Tests\TestCase;

class CollectionTest extends TestCase
{
    protected function collect(?iterable $items = null): Collection
    {
        return new Collection($items);
    }

    public function testAverage()
    {
        $average = $this->collect([1, 2])->average();
        self::assertEquals(1.5, $average);
    }

    public function testChunk()
    {
        // chunking empty returns new blank instance
        $empty = $this->collect();
        $result = $empty->chunk(1);
        self::assertEmpty($result);
        self::assertNotSame($empty, $result);

        $seq = $this->collect([1, 2, 3]);

        // test preserveKeys: true
        $chunked = $seq->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals([0 => 1, 1 => 2], $chunked[0]->toArray());
        self::assertEquals([2 => 3], $chunked[1]->toArray());

        // test preserveKeys: false
        $chunked = $seq->chunk(2, false);
        self::assertCount(2, $chunked);
        self::assertEquals([1, 2], $chunked[0]->toArray());
        self::assertEquals([3], $chunked[1]->toArray());

        // size larger than items -> returns everything
        $chunked = $seq->chunk(4, false);
        self::assertCount(1, $chunked);
        self::assertEquals([1, 2, 3], $chunked[0]->toArray());
        self::assertNotSame($chunked, $seq);

        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);

        // test preserveKeys: true
        $chunked = $assoc->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2], $chunked[0]->toArray());
        self::assertEquals(['c' => 3], $chunked[1]->toArray());

        // test preserveKeys: false
        $chunked = $assoc->chunk(2, false);
        self::assertCount(2, $chunked);
        self::assertEquals([1, 2], $chunked[0]->toArray());
        self::assertEquals([3], $chunked[1]->toArray());

        // size larger than items -> returns everything
        $chunked = $assoc->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $chunked[0]->toArray());
        self::assertNotSame($chunked, $assoc);
    }

    public function testChunkInvalidSize()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('array_chunk(): Size parameter expected to be greater than 0');
        $this->collect([1])->chunk(0);
    }

    public function testCompact()
    {
        // compact empty returns new blank instance
        $empty = $this->collect();
        self::assertNotSame($empty, $empty->compact());

        // sequence: removes nulls
        $seq = $this->collect([1, null, null, 2]);
        $compacted = $seq->compact();
        self::assertNotSame($seq, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals([0 => 1, 3 => 2], $compacted->toArray());

        // sequence: no nulls
        $seq = $this->collect([1, 2]);
        $compacted = $seq->compact();
        self::assertNotSame($seq, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals([0 => 1, 1 => 2], $compacted->toArray());

        // sequence: all nulls
        $seq = $this->collect([null, null]);
        $compacted = $seq->compact();
        self::assertNotSame($seq, $compacted);
        self::assertEmpty($compacted->toArray());
        self::assertEquals([], $compacted->toArray());

        // assoc: removes nulls
        $assoc = $this->collect(['a' => null, 'b' => 1, 'c' => 2, 'd' => null]);
        $compacted = $assoc->compact();
        self::assertNotSame($assoc, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals(['b' => 1, 'c' => 2], $compacted->toArray());

        // assoc: no nulls
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        $compacted = $assoc->compact();
        self::assertNotSame($assoc, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals(['a' => 1, 'b' => 2], $compacted->toArray());

        // assoc: all nulls
        $assoc = $this->collect(['a' => null, 'b' => null]);
        $compacted = $assoc->compact();
        self::assertNotSame($assoc, $compacted);
        self::assertEmpty($compacted->toArray());
        self::assertEquals([], $compacted->toArray());
    }

    public function testContains()
    {
        $empty = $this->collect();
        self::assertFalse($empty->contains(null));
        self::assertFalse($empty->contains(static fn() => true));

        // sequence: compared with value
        $seq = $this->collect([1, null, 2, [3], false]);
        self::assertTrue($seq->contains(1));
        self::assertTrue($seq->contains(null));
        self::assertTrue($seq->contains([3]));
        self::assertTrue($seq->contains(false));
        self::assertFalse($seq->contains(3));
        self::assertFalse($seq->contains([]));

        // sequence: compared with callback
        $seq = $this->collect([1, null, 2, [3], false]);
        self::assertTrue($seq->contains(static fn($v) => true));
        self::assertFalse($seq->contains(static fn($v) => false));
        self::assertTrue($seq->contains(static fn($v) => is_array($v)));

        // assoc: compared with value
        $assoc = $this->collect(['a' => 1]);
        self::assertTrue($assoc->contains(1));
        self::assertFalse($assoc->contains(['a' => 1]));
        self::assertFalse($assoc->contains(['a']));

        // assoc: compared with callback
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($assoc->contains(static fn($v, $k) => true));
        self::assertFalse($assoc->contains(static fn($v) => false));
        self::assertTrue($assoc->contains(static fn($v, $k) => $k === 'b'));
    }

    public function testCopy()
    {

    }
}
