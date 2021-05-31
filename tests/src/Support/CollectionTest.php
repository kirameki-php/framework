<?php

namespace Tests\Kirameki\Support;

use ArrayIterator;
use ErrorException;
use Generator;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidKeyException;
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
        $collect = $this->collect([1, null, 2, [3], false]);
        self::assertTrue($collect->contains(1));
        self::assertTrue($collect->contains(null));
        self::assertTrue($collect->contains([3]));
        self::assertTrue($collect->contains(false));
        self::assertFalse($collect->contains(3));
        self::assertFalse($collect->contains([]));

        // sequence: compared with callback
        $collect = $this->collect([1, null, 2, [3], false]);
        self::assertTrue($collect->contains(static fn($v) => true));
        self::assertFalse($collect->contains(static fn($v) => false));
        self::assertTrue($collect->contains(static fn($v) => is_array($v)));

        // assoc: compared with value
        $collect = $this->collect(['a' => 1]);
        self::assertTrue($collect->contains(1));
        self::assertFalse($collect->contains(['a' => 1]));
        self::assertFalse($collect->contains(['a']));

        // assoc: compared with callback
        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->contains(static fn($v, $k) => true));
        self::assertFalse($collect->contains(static fn($v) => false));
        self::assertTrue($collect->contains(static fn($v, $k) => $k === 'b'));
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

    public function testDrop()
    {
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['b' => 2, 'c' => 3], $collect->drop(1)->toArray());

        // over value
        $collect = $this->collect(['a' => 1]);
        self::assertEquals([], $collect->drop(2)->toArray());

        // negative
        $collect = $this->collect(['a' => 1]);
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be positive value. -1 given.');
        $collect->drop(-1)->toArray();
    }

    public function testDropUntil()
    {
        // look at value
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $collect->dropUntil(fn($v) => $v >= 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $collect->dropUntil(fn($v, $k) => $k === 'c')->toArray());

        // drop until null does not work
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be bool. null given.');
        $collect->dropUntil(fn($v, $k) => null)->toArray();
    }

    public function testDropWhile()
    {
        // look at value
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $collect->dropWhile(fn($v) => $v < 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $collect->dropWhile(fn($v, $k) => $k !== 'c')->toArray());

        // drop until null does not work
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be bool. null given.');
        $collect->dropWhile(fn($v, $k) => null)->toArray();
    }

    public function testEach()
    {
        $collect = $this->collect(['a' => 1, 'b' => 2]);
        $collect->each(function ($v, $k) {
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
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        $collect->eachChunk(2, function (Collection $chunk, int $count) {
            if ($count === 0) self::assertEquals(['a' => 1, 'b' => 2], $chunk->toArray());
            if ($count === 1) self::assertEquals(['c' => 3], $chunk->toArray());
        });

        // chunk larger than assoc length
        $collect = $this->collect(['a' => 1]);
        $collect->eachChunk(2, function (Collection $chunk) {
            self::assertEquals(['a' => 1], $chunk->toArray());
        });
    }

    public function testEachChunk_NegativeValue()
    {
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be positive int. -2 given.');
        $collect->eachChunk(-2, fn() => null);
    }

    public function testEachWithIndex()
    {
        $collect = $this->collect(['a' => 1, 'b' => 2]);
        $collect->eachWithIndex(function ($v, $k, $n) {
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
        $collect = $this->collect($arr);
        self::assertTrue($collect->equals($arr));
        self::assertTrue($collect->equals($collect));
        self::assertTrue($collect->equals($this->collect($arr)));
        self::assertFalse($collect->equals([]));
    }

    public function testExcept()
    {
        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $collect->except('a')->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $collect->except('a', 'c')->toArray());
    }

    public function testFilter()
    {
        // sequence: remove ones with empty value
        $collect = $this->collect([0, 1, '', '0', null]);
        self::assertEquals([1 => 1], $collect->filter()->toArray());

        // assoc: removes null / false / 0 / empty string / empty array
        $collect = $this->collect(['a' => null, 'b' => false, 'c' => 0, 'd' => '', 'e' => '0', 'f' => []]);
        self::assertEquals([], $collect->filter()->toArray());

        // assoc: removes ones with condition
        self::assertEquals(['d' => ''], $collect->filter(fn($v) => $v === '')->toArray());
    }

    public function testFirst()
    {
        $collect = $this->collect([10, 20]);
        self::assertEquals(10, $collect->first());
        self::assertEquals(20, $collect->first(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->first(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->first(fn() => false));
    }

    public function testFirstIndex()
    {
        $collect = $this->collect([10, 20, 20, 30]);
        self::assertEquals(2, $collect->firstIndex(fn($v, $k) => $k === 2));
        self::assertEquals(1, $collect->firstIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->firstIndex(fn() => false));
    }

    public function testFirstKey()
    {
        $collect = $this->collect([10, 20, 30]);
        self::assertEquals(1, $collect->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals(2, $collect->firstKey(fn($v, $k) => $k === 2));

        $collect = $this->collect(['a' => 10, 'b' => 20, 'c' => 30]);
        self::assertEquals('b', $collect->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals('c', $collect->firstKey(fn($v, $k) => $k === 'c'));
    }

    public function testFlatMap()
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, -1, 2, -2], $collect->flatMap(fn($i) => [$i, -$i])->toArray());

        $collect = $this->collect([['a'], ['b']]);
        self::assertEquals(['a', 'b'], $collect->flatMap(fn($a) => $a)->toArray());

        $collect = $this->collect([['a' => 1], [2], 2]);
        self::assertEquals([1, 2, 2], $collect->flatMap(fn($a) => $a)->toArray());
    }

    public function testFlatten()
    {
        // nothing to flatten
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2], $collect->flatten()->toArray());

        // flatten only 1 as default
        $collect = $this->collect([[1, [2, 2]], 3]);
        self::assertEquals([1, [2, 2], 3], $collect->flatten()->toArray());

        // flatten more than 1
        $collect = $this->collect([['a' => 1], [1, [2, [3, 3], 2], 1]]);
        self::assertEquals([1, 1, 2, [3, 3], 2, 1], $collect->flatten(2)->toArray());

        // assoc info is lost
        $collect = $this->collect([['a'], 'b', ['c' => 'd']]);
        self::assertEquals(['a', 'b', 'd'], $collect->flatten()->toArray());
    }

    public function testFlatten_ZeroDepth()
    {
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be positive int. 0 given.');
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2], $collect->flatten(0)->toArray());
    }

    public function testFlatten_NegativeDepth()
    {
        self::expectException(InvalidValueException::class);
        self::expectExceptionMessage('Expected value to be positive int. -1 given.');
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2], $collect->flatten(-1)->toArray());
    }

    public function testFlip()
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals([1 => 0, 2 => 1], $collect->flip()->toArray());

        $collect = $this->collect(['a' => 'b', 'c' => 'd']);
        self::assertEquals(['b' => 'a', 'd' => 'c'], $collect->flip()->toArray());
    }

    public function testGet()
    {
        $collect = $this->collect([1, 2]);
        $collect->pull("");
        self::assertEquals(2, $collect->get(1));

        $collect = $this->collect(['a' => [1, 'b' => 2, 'c' => ['d' => 3]], 'c' => 'd', 'e' => []]);
        // get existing data
        self::assertEquals([1, 'b' => 2, 'c' => ['d' => 3]], $collect->get('a'));
        self::assertEquals('d', $collect->get('c'));
        // get non-existing data
        self::assertNull($collect->get('a.e'));
        self::assertEquals(null, $collect->get(0));
        // get existing data with dot
        self::assertEquals(1, $collect->get('a.0'));
        self::assertEquals(2, $collect->get('a.b'));
        self::assertEquals(3, $collect->get('a.c.d'));
        self::assertEquals([], $collect->get('e'));
    }

    public function testGetIterator()
    {
        $iterator = $this->collect()->getIterator();
        self::assertInstanceOf(ArrayIterator::class, $iterator);
    }

    public function testGroupBy()
    {
        $collect = $this->collect([1, 2, 3, 4, 5, 6]);
        self::assertEquals([[3, 6], [1, 4], [2, 5]], $collect->groupBy(fn($n) => $n % 3)->toArrayRecursive());

        $collect = $this->collect([
            ['id' => 1],
            ['id' => 1],
            ['id' => 2],
            ['dummy' => 3],
        ]);
        self::assertEquals([1 => [['id' => 1], ['id' => 1]], 2 => [['id' => 2]]], $collect->groupBy('id')->toArrayRecursive());
    }

    public function testImplode()
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals('1, 2', $collect->implode(', '));
        self::assertEquals('[1, 2', $collect->implode(', ', '['));
        self::assertEquals('[1, 2]', $collect->implode(', ', '[', ']'));

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals('1, 2', $collect->implode(', '));
        self::assertEquals('[1, 2', $collect->implode(', ', '['));
        self::assertEquals('[1, 2]', $collect->implode(', ', '[', ']'));
    }

    public function testInsertAt()
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals(['a', 1, 2], $collect->insertAt(0, 'a')->toArray());

        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 'a', 2], $collect->insertAt(1, 'a')->toArray());

        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2, 'a'], $collect->insertAt(10, 'a')->toArray());

        $collect = $this->collect([1, 2, 3, 4]);
        self::assertEquals([1, 2, 3, 4, 'a'], $collect->insertAt(-1, 'a')->toArray());

        $collect = $this->collect([1, 2, 3, 4]);
        self::assertEquals([1, 2, 3, 'a', 4], $collect->insertAt(-2, 'a')->toArray());
    }

    public function testIntersect()
    {
        $collect = $this->collect([1, 2, 3]);
        self::assertEquals([1], $collect->intersect([1])->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $collect->intersect([1])->toArray());

        $collect = $this->collect([]);
        self::assertEquals([], $collect->intersect([1])->toArray());
    }

    public function testIntersectKeys()
    {
        $collect = $this->collect([1, 2, 3]);
        self::assertEquals([1, 2], $collect->intersectKeys([1, 3])->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals([], $collect->intersectKeys([1])->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $collect->intersectKeys(['a' => 2])->toArray());

        $collect = $this->collect([]);
        self::assertEquals([], $collect->intersectKeys(['a' => 1])->toArray());

        $collect = $this->collect(['a' => 1]);
        self::assertEquals([], $collect->intersectKeys([])->toArray());
    }

    public function testIsAssoc()
    {
        $collect = $this->collect([]);
        self::assertTrue($collect->isAssoc());

        $collect = $this->collect([1, 2]);
        self::assertFalse($collect->isAssoc());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->isAssoc());
    }

    public function testIsEmpty()
    {
        $collection = $this->collect([]);
        self::assertTrue($collection->isEmpty());

        $collection = $this->collect([1, 2]);
        self::assertFalse($collection->isEmpty());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertFalse($collect->isEmpty());
    }

    public function testIsNotEmpty()
    {
        $collect = $this->collect([]);
        self::assertFalse($collect->isNotEmpty());

        $collect = $this->collect([1, 2]);
        self::assertTrue($collect->isNotEmpty());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->isNotEmpty());
    }

    public function testIsList()
    {
        $collect = $this->collect([]);
        self::assertTrue($collect->isList());

        $collect = $this->collect([1, 2]);
        self::assertTrue($collect->isList());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertFalse($collect->isList());
    }

    public function testJsonSerialize()
    {
        $collect = $this->collect([]);
        self::assertEquals([], $collect->jsonSerialize());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->jsonSerialize());
    }

    public function testKeyBy()
    {
        $collect = $this->collect([1, 2])->keyBy(fn($v) => 'a'.$v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $collect->toArray());

        $collect = $this->collect([['id' => 'b'], ['id' => 'c']])->keyBy(fn($v) => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $collect->toArray());
    }

    public function testKeyBy_WithDuplicateKey()
    {
        self::expectException(DuplicateKeyException::class);
        $this->collect([['id' => 'b'], ['id' => 'b']])->keyBy(fn($v) => $v['id']);
    }

    public function testKeyBy_WithOverwritableKey()
    {
        $collect = $this->collect([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $collect->toArray());
    }

    public function testKeys()
    {
        $keys = $this->collect([1,2])->keys();
        self::assertEquals([0,1], $keys->toArray());

        $keys = $this->collect(['a' => 1, 'b' => 2])->keys();
        self::assertEquals(['a', 'b'], $keys->toArray());
    }

    public function testLast()
    {
        $collect = $this->collect([10, 20]);
        self::assertEquals(20, $collect->last());
        self::assertEquals(20, $collect->last(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->last(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->last(fn() => false));
    }

    public function testLastIndex()
    {
        $collect = $this->collect([10, 20, 20]);
        self::assertEquals(1, $collect->lastIndex(fn($v, $k) => $k === 1));
        self::assertEquals(2, $collect->lastIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->lastIndex(fn() => false));
    }

    public function testLastKey()
    {
        $collect = $this->collect(['a' => 10, 'b' => 20, 'c' => 20]);
        self::assertEquals('c', $collect->lastKey());
        self::assertEquals('b', $collect->lastKey(fn($v, $k) => $k === 'b'));
        self::assertEquals('c', $collect->lastKey(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->lastKey(fn() => false));
    }

    public function testMacro()
    {
        Collection::macro('testMacro', fn($num) => $num * 100);
        $collect = $this->collect([1]);
        self::assertEquals(200, $collect->testMacro(2));
    }

    public function testMacroExists()
    {
        self::assertFalse(Collection::macroExists('testMacro2'));
        Collection::macro('testMacro2', fn() => 1);
        self::assertTrue(Collection::macroExists('testMacro2'));
    }

    public function testMap()
    {
        $collect = $this->collect([1, 2, 3]);
        self::assertEquals([2, 4, 6], $collect->map(fn($i) => $i * 2)->toArray());
        self::assertEquals([0, 1, 2], $collect->map(fn($i, $k) => $k)->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 2, 'b' => 4, 'c' => 6], $collect->map(fn($i) => $i * 2)->toArray());
    }

    public function testMax()
    {
        $collect = $this->collect([1, 2, 3, 10, 1]);
        self::assertEquals(10, $collect->max());

        $collect = $this->collect([100, 2, 3, 10, 1]);
        self::assertEquals(100, $collect->max());

        $collect = $this->collect([1, 2, 3, 10, 1, -100, 90]);
        self::assertEquals(90, $collect->max());
    }

    public function testMerge()
    {

    }

    public function testMin()
    {
        $collect = $this->collect([1, 2, 3, 10, -1]);
        self::assertEquals(-1, $collect->min());

        $collect = $this->collect([0, -1]);
        self::assertEquals(-1, $collect->min());

        $collect = $this->collect([1, 10, -100]);
        self::assertEquals(-100, $collect->min());
    }

    public function testMinMax()
    {
        $collect = $this->collect([]);
        self::assertEquals([null, null], $collect->minMax());

        $collect = $this->collect([1]);
        self::assertEquals([1, 1], $collect->minMax());

        $collect = $this->collect([1, 10, -100]);
        self::assertEquals([-100, 10], $collect->minMax());
    }




    public function testOffsetExists()
    {
        $seq = $this->collect([1, 2]);
        self::assertTrue(isset($seq[0]));

        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue(isset($assoc['b']));

        $assoc = $this->collect([]);
        self::assertFalse(isset($assoc['a']));
    }

    public function testOffsetGet()
    {
        $seq = $this->collect([1, 2]);
        self::assertEquals(1, $seq[0]);

        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $assoc['b']);
    }

    public function testOffsetGet_UndefinedKey()
    {
        self::expectException(ErrorException::class);
        self::expectExceptionMessage('Undefined array key "e"');
        $this->collect(['a' => 1, 'b' => 2])['e'];
    }

    public function testOffsetSet()
    {
        // push number
        $seq = $this->collect([1, 2]);
        $seq[] = 3;
        self::assertEquals([1, 2, 3], $seq->toArray());

        // jump offset number from 0, 1, 3
        $seq = $this->collect([1, 2]);
        $seq[3] = 3;
        self::assertEquals([1, 2, 3 => 3], $seq->toArray());

        // set offset with string
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        $assoc['c'] = 3;
        self::assertEquals(3, $assoc['c']);
    }

    public function testOffsetSet_BoolAsKey()
    {
        self::expectException(InvalidKeyException::class);
        $this->collect([])->set(true, 1);
    }

    public function testOffsetSet_FloatAsKey()
    {
        self::expectException(InvalidKeyException::class);
        $this->collect([])->set(1.1, 1);
    }

    public function testOffsetUnset()
    {
        $seq = $this->collect([1, 2]);
        unset($seq[0]);
        self::assertEquals([1 => 2], $seq->toArray());

        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        unset($assoc['b']);
        self::assertEquals(['a' => 1], $assoc->toArray());

        $assoc = $this->collect([]);
        unset($assoc['b']);
        self::assertEquals([], $assoc->toArray());
    }

}
