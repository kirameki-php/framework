<?php

namespace Tests\Kirameki\Support;

use ErrorException;
use Exception;
use Generator;
use Kirameki\Exception\UnexpectedArgumentException;
use Kirameki\Exception\InvalidValueException;
use Kirameki\Support\Collection;
use Tests\Kirameki\TestCase;
use TypeError;
use ValueError;

class CollectionTest extends TestCase
{
    protected function collect(?iterable $items = null): Collection
    {
        return new Collection($items);
    }

    public function test__Construct()
    {
        // empty
        $empty = new Collection();
        self::assertEquals([], $empty->toArray());

        // ordinal
        $ordinal = new Collection([1, 2]);
        self::assertEquals([1, 2], $ordinal->toArray());

        // assoc
        $assoc = new Collection(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $assoc->toArray());

        // array in collection
        $inner = new Collection([1]);
        $collection = new Collection([$inner]);
        self::assertEquals([$inner], $collection->toArray());

        // iterable in collection
        $collection = new Collection(new Collection([1, 2]));
        self::assertEquals([1, 2], $collection->toArray());
    }

    public function test__Construct_BadArgument()
    {
        self::expectException(TypeError::class);
        self::expectExceptionMessage('Argument #1 ($items) must be of type ?iterable, int given');
        new Collection(1);
    }

    public function testAverage()
    {
        $average = $this->collect([1, 2])->average();
        self::assertEquals(1.5, $average);
    }

    public function testChunk()
    {
        // empty but not same instance
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
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('array_chunk(): Argument #2 ($length) must be greater than 0');
        $this->collect([1])->chunk(0);
    }

    public function testCompact()
    {
        // empty but not same instance
        $empty = $this->collect();
        self::assertNotSame($empty, $empty->compact());

        // sequence: removes nulls
        $compacted = $this->collect([1, null, null, 2])->compact();
        self::assertCount(2, $compacted);
        self::assertEquals([0 => 1, 3 => 2], $compacted->toArray());

        // sequence: no nulls
        $seq = $this->collect([1, 2]);
        $compacted = $seq->compact();
        self::assertNotSame($seq, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals([0 => 1, 1 => 2], $compacted->toArray());

        // sequence: all nulls
        $compacted = $this->collect([null, null])->compact();
        self::assertEmpty($compacted->toArray());
        self::assertEquals([], $compacted->toArray());

        // assoc: removes nulls
        $assoc = $this->collect(['a' => null, 'b' => 1, 'c' => 2, 'd' => null]);
        $compacted = $assoc->compact();
        self::assertCount(2, $compacted);
        self::assertEquals(['b' => 1, 'c' => 2], $compacted->toArray());

        // assoc: no nulls
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        $compacted = $assoc->compact();
        self::assertNotSame($assoc, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals(['a' => 1, 'b' => 2], $compacted->toArray());

        // assoc: all nulls
        $compacted = $this->collect(['a' => null, 'b' => null])->compact();
        self::assertEmpty($compacted->toArray());
        self::assertEquals([], $compacted->toArray());

        // depth = INT_MAX
        $compacted = $this->collect(['a' => ['b' => ['c' => null]], 'b' => null])->compact();
        self::assertEquals(['a' => ['b' => []]], $compacted->toArray());

        // depth = 1
        $compacted = $this->collect(['a' => ['b' => null], 'b' => null])->compact(1);
        self::assertEquals(['a' => ['b' => null]], $compacted->toArray());
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

    public function testContainsKey()
    {
        // empty but not same instance
        $empty = $this->collect();
        self::assertFalse($empty->containsKey('a'));
        self::assertFalse($empty->containsKey('a.a'));
        self::assertEmpty($empty->containsKey(0));
        self::assertEmpty($empty->containsKey(-1));

        // copy sequence
        $seq = $this->collect([-2 => 1, 3, 4, [1, 2, [1, 2, 3]], [null]]);
        self::assertTrue($seq->containsKey(1));
        self::assertTrue($seq->containsKey('1'));
        self::assertTrue($seq->containsKey('-2'));
        self::assertTrue($seq->containsKey(-2));
        self::assertTrue($seq->containsKey(-1));
        self::assertFalse($seq->containsKey(999));
        self::assertFalse($seq->containsKey('0.3'));
        self::assertFalse($seq->containsKey('2.999'));
        self::assertFalse($seq->containsKey("1.1.1"));
        self::assertTrue($seq->containsKey("1.2"));
        self::assertTrue($seq->containsKey("1.2.2"));
        self::assertFalse($seq->containsKey("1.2.2.-2"));
        self::assertTrue($seq->containsKey("2"));

        // copy assoc
        $assoc = $this->collect(['a' => [1, 2, 3], '-' => 'c', 'd' => ['e'], 'f' => null]);
        self::assertTrue($assoc->containsKey('a'));
        self::assertFalse($assoc->containsKey('a.a'));
        self::assertTrue($assoc->containsKey('d.0'));
        self::assertTrue($assoc->containsKey('f'));
    }

    public function testCopy()
    {
        // empty but not same instance
        $empty = $this->collect();
        $clone = $empty->copy();
        self::assertNotSame($empty, $clone);
        self::assertEmpty($clone);

        // copy sequence
        $seq = $this->collect([3, 4]);
        $clone = $seq->copy();
        self::assertNotSame($seq, $clone);
        self::assertEquals([3, 4], $seq->toArray());

        // copy assoc
        $seq = $this->collect(['a' => 3, 'b' => 4]);
        $clone = $seq->copy();
        self::assertNotSame($seq, $clone);
        self::assertEquals(['a' => 3, 'b' => 4], $seq->toArray());
    }

    public function testCount()
    {
        // empty
        $empty = $this->collect();
        self::assertEquals(0, $empty->count());

        // count default
        $simple = $this->collect([1, 2, 3]);
        self::assertEquals(3, $simple->count());
    }

    public function testCountBy()
    {
        $simple = $this->collect([1, 2, 3]);
        self::assertEquals(2, $simple->countBy(fn($v) => $v > 1));
    }

    public function testCursor()
    {
        $array = ['a' => 1, 'b' => 2];
        $simple = $this->collect($array);
        self::assertInstanceOf(Generator::class, $simple->cursor());
        self::assertSame($array, iterator_to_array($simple->cursor()));
    }

    public function testDeepMerge()
    {
        $empty = $this->collect();
        $merged = $empty->deepMerge([1, [2]]);
        self::assertNotSame($empty, $merged);
        self::assertCount(0, $empty);
        self::assertCount(2, $merged);
        self::assertEquals([1, [2]], $merged->toArray());

        $assoc = $this->collect([1, 'a' => [1, 2]]);
        $merged = $assoc->deepMerge([1, 'a' => [3]]);
        self::assertCount(2, $assoc);
        self::assertCount(3, $merged);
        self::assertNotSame($assoc, $merged);
        self::assertSame([1, 'a' => [1, 2, 3], 1], $merged->toArray());
    }

    public function testDiff()
    {
        $empty = $this->collect();
        $diffed = $empty->diff([1]);
        self::assertNotSame($empty, $diffed);
        self::assertCount(0, $empty);
        self::assertCount(0, $diffed);

        $original = [-1, 'a' => 1, 'b' => 2, 3];
        $differ = [2, 3, 'a' => 1, 'c' => 2, 5];
        $assoc = $this->collect($original);
        $diffed = $assoc->diff($differ);
        self::assertNotSame($assoc, $diffed);
        self::assertSame($original, $assoc->toArray());
        self::assertSame([-1], $diffed->toArray());
    }

    public function testDiffKeys()
    {
        $empty = $this->collect();
        $diffed = $empty->diffKeys([-1]);
        self::assertNotSame($empty, $diffed);
        self::assertCount(0, $empty);
        self::assertCount(0, $diffed);

        $original = [-1, 'a' => 1, 'b' => 2, 3, -10 => -10];
        $differ = [2, 3, 'a' => 1, 'c' => 2, 5];
        $assoc = $this->collect($original);
        $diffed = $assoc->diffKeys($differ);
        self::assertNotSame($assoc, $diffed);
        self::assertSame($original, $assoc->toArray());
        self::assertSame(['b' => 2, -10 => -10], $diffed->toArray());
    }

    public function testDig()
    {
        $assoc = $this->collect(['one' => ['two' => [1, 2], 'three' => 4, 'four' => []]]);
        $dug = $assoc->dig('nothing');
        self::assertNull($dug);

        $dug = $assoc->dig('one.nothing');
        self::assertNull($dug);

        $dug = $assoc->dig('one.two');
        self::assertCount(2, $dug);
        self::assertEquals([1, 2], $dug->toArray());

        $dug = $assoc->dig('one.two.three');
        self::assertNull($dug);

        $dug = $assoc->dig('one.two.0');
        self::assertNull($dug);

        $dug = $assoc->dig('one.four');
        self::assertEquals([], $dug->toArray());
    }

    public function testDrop()
    {
        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['b' => 2, 'c' => 3], $assoc->drop(1)->toArray());

        // over value
        $assoc = $this->collect(['a' => 1]);
        self::assertEquals([], $assoc->drop(2)->toArray());

        // negative
        $assoc = $this->collect(['a' => 1]);
        self::expectException(UnexpectedArgumentException::class);
        self::expectExceptionMessage('Kirameki\Support\Collection::drop() Argument #0 ($amount) must be positive value, -1 given.');
        $assoc->drop(-1)->toArray();
    }

    public function testDropUntil()
    {
        // look at value
        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $assoc->dropUntil(fn($v) => $v >= 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $assoc->dropUntil(fn($v, $k) => $k === 'c')->toArray());

        // drop until null does not work
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be bool. null given.');
        $assoc->dropUntil(fn($v, $k) => null)->toArray();
    }

    public function testDropWhile()
    {
        // look at value
        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $assoc->dropWhile(fn($v) => $v < 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $assoc->dropWhile(fn($v, $k) => $k !== 'c')->toArray());

        // drop until null does not work
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be bool. null given.');
        $assoc->dropWhile(fn($v, $k) => null)->toArray();
    }

    public function testEach()
    {
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        $assoc->each(function ($v, $k) {
            switch ($k) {
                case 'a':
                    self::assertEquals(['a' => 1], [$k => $v]);
                    break;
                case 'b':
                    self::assertEquals(['b' => 2], [$k => $v]);
                    break;
            }
        });
    }

    public function testEachChunk()
    {
        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        $assoc->eachChunk(2, function (Collection $chunk, int $count) {
            if ($count === 0) self::assertEquals(['a' => 1, 'b' => 2], $chunk->toArray());
            if ($count === 1) self::assertEquals(['c' => 3], $chunk->toArray());
        });

        // chunk larger than assoc length
        $assoc = $this->collect(['a' => 1]);
        $assoc->eachChunk(2, function (Collection $chunk) {
            self::assertEquals(['a' => 1], $chunk->toArray());
        });
    }

    public function testEachChunk_NegativeValue()
    {
        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be positive int. -2 given.');
        $assoc->eachChunk(-2, fn() => null);
    }

    public function testEachWithIndex()
    {
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        $assoc->eachWithIndex(function ($v, $k, $n) {
            switch ($k) {
                case 'a':
                    self::assertEquals(['a' => 1], [$k => $v]);
                    self::assertEquals(0, $n);
                    break;
                case 'b':
                    self::assertEquals(['b' => 2], [$k => $v]);
                    self::assertEquals(1, $n);
                    break;
            }
        });
    }

    public function testEquals()
    {
        $arr = ['a' => 1, 'b' => 2];
        $assoc = $this->collect($arr);
        self::assertTrue($assoc->equals($arr));
        self::assertTrue($assoc->equals($assoc));
        self::assertTrue($assoc->equals($this->collect($arr)));
        self::assertFalse($assoc->equals([]));
    }

    public function testExcept()
    {
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $assoc->except('a')->toArray());

        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $assoc->except('a', 'c')->toArray());
    }

    public function testFilter()
    {
        // sequence: remove ones with empty value
        $seq = $this->collect([0, 1, '', '0', null]);
        self::assertEquals([1 => 1], $seq->filter()->toArray());

        // assoc: removes null / false / 0 / empty string / empty array
        $assoc = $this->collect(['a' => null, 'b' => false, 'c' => 0, 'd' => '', 'e' => '0', 'f' => []]);
        self::assertEquals([], $assoc->filter()->toArray());

        // assoc: removes ones with condition
        self::assertEquals(['d' => ''], $assoc->filter(fn($v) => $v === '')->toArray());
    }

    public function testFirst()
    {
        $seq = $this->collect([10, 20]);
        self::assertEquals(10, $seq->first());
        self::assertEquals(20, $seq->first(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->first(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->first(fn() => false));
    }

    public function testFirstIndex()
    {
        $seq = $this->collect([10, 20, 30]);
        self::assertEquals(2, $seq->firstIndex(fn($v, $k) => $k === 2));
        self::assertEquals(1, $seq->firstIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->firstIndex(fn() => false));
    }

    public function testFirstKey()
    {
        $seq = $this->collect([10, 20, 30]);
        self::assertEquals(1, $seq->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals(2, $seq->firstKey(fn($v, $k) => $k === 2));

        $assoc = $this->collect(['a' => 10, 'b' => 20, 'c' => 30]);
        self::assertEquals('b', $assoc->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals('c', $assoc->firstKey(fn($v, $k) => $k === 'c'));
    }

    public function testFlatMap()
    {
        $assoc = $this->collect(['a' => ['b' => 1], 'b' => 2]);
        $flat = $assoc->flatMap(fn($a) => $a)->toArray();
    }
}
