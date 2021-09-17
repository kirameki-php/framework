<?php declare(strict_types=1);

namespace Tests\Kirameki\Support;

use DivisionByZeroError;
use ErrorException;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidKeyException;
use Kirameki\Exception\InvalidValueException;
use Kirameki\Support\Collection;
use RuntimeException;
use Tests\Kirameki\TestCase;
use TypeError;
use ValueError;

class CollectionTest extends TestCase
{
    protected function collect(?iterable $items = null): Collection
    {
        return new Collection($items);
    }

    public function test__Construct(): void
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

    public function test__Construct_BadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument #1 ($items) must be of type ?iterable, int given');
        new Collection(1);
    }

    public function testAt(): void
    {
        self::assertEquals(null, $this->collect()->at(0));
        self::assertEquals(null, $this->collect([1, 2, 3])->at(5));
        self::assertEquals(null, $this->collect([1, 2, 3])->at(PHP_INT_MIN));
        self::assertEquals(null, $this->collect([1, 2, 3])->at(PHP_INT_MAX));

        self::assertEquals(1, $this->collect([1, 2, 3])->at(0));
        self::assertEquals(2, $this->collect([1, 2, 3])->at(1));
        self::assertEquals(3, $this->collect([1, 2, 3])->at(-1));

        self::assertEquals(1, $this->collect(['a' => 1, 'b' => 2, 'c' => 3])->at(0));
        self::assertEquals(2, $this->collect(['a' => 1, 'b' => 2, 'c' => 3])->at(1));
        self::assertEquals(3, $this->collect(['a' => 1, 'b' => 2, 'c' => 3])->at(-1));
    }

    public function testAverage(): void
    {
        $average = $this->collect([])->average();
        self::assertEquals(0, $average);

        $average = $this->collect([1, 2])->average(allowEmpty: false);
        self::assertEquals(1.5, $average);

        $average = $this->collect([1, 2])->average();
        self::assertEquals(1.5, $average);

        $average = $this->collect([1, 2, 3])->average();
        self::assertEquals(2, $average);

        $average = $this->collect([0, 0, 0])->average();
        self::assertEquals(0, $average);
    }

    public function testAverage_NotEmpty(): void
    {
        $this->expectException(DivisionByZeroError::class);
        $this->collect([])->average(allowEmpty: false);
    }

    public function testChunk(): void
    {
        // empty but not same instance
        $empty = $this->collect();
        $result = $empty->chunk(1);
        self::assertEmpty($result);
        self::assertNotSame($empty, $result);

        $seq = $this->collect([1, 2, 3]);

        $chunked = $seq->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals([1, 2], $chunked[0]->toArray());
        self::assertEquals([3], $chunked[1]->toArray());

        // size larger than items -> returns everything
        $chunked = $seq->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals([1, 2, 3], $chunked[0]->toArray());
        self::assertNotSame($chunked, $seq);

        $assoc = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);

        // test preserveKeys: true
        $chunked = $assoc->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2], $chunked[0]->toArray());
        self::assertEquals(['c' => 3], $chunked[1]->toArray());

        // size larger than items -> returns everything
        $chunked = $assoc->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $chunked[0]->toArray());
        self::assertNotSame($chunked, $assoc);
    }

    public function testChunkInvalidSize(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('array_chunk(): Argument #2 ($length) must be greater than 0');
        $this->collect([1])->chunk(0);
    }

    public function testCompact(): void
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
        $compacted = $this->collect(['a' => ['b' => ['c' => null]], 'b' => null])->compact(PHP_INT_MAX);
        self::assertEquals(['a' => ['b' => []]], $compacted->toArray());

        // depth = 1
        $compacted = $this->collect(['a' => ['b' => null], 'b' => null])->compact();
        self::assertEquals(['a' => ['b' => null]], $compacted->toArray());
    }

    public function testContains(): void
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

    public function testContainsKey(): void
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

    public function testCopy(): void
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

    public function testCount(): void
    {
        // empty
        $empty = $this->collect();
        self::assertEquals(0, $empty->count());

        // count default
        $simple = $this->collect([1, 2, 3]);
        self::assertEquals(3, $simple->count());
    }

    public function testCountBy(): void
    {
        $simple = $this->collect([1, 2, 3]);
        self::assertEquals(2, $simple->countBy(fn($v) => $v > 1));
    }

    public function testCursor(): void
    {
        $array = ['a' => 1, 'b' => 2];
        $simple = $this->collect($array);
        self::assertSame($array, iterator_to_array($simple->cursor()));
    }

    public function testDiff(): void
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

    public function testDiffKeys(): void
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

    public function testDrop(): void
    {
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['b' => 2, 'c' => 3], $collect->drop(1)->toArray());

        // over value
        $collect = $this->collect(['a' => 1]);
        self::assertEquals([], $collect->drop(2)->toArray());

        // negative
        $collect = $this->collect(['a' => 1, 'b' => 1]);
        self::assertEquals(['a' => 1], $collect->drop(-1)->toArray());

        // zero
        $collect = $this->collect(['a' => 1]);
        self::assertEquals(['a' => 1], $collect->drop(0)->toArray());
    }

    public function testDropUntil(): void
    {
        // look at value
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $collect->dropUntil(fn($v) => $v >= 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $collect->dropUntil(fn($v, $k) => $k === 'c')->toArray());

        // drop until null does not work
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be bool. null given.');
        $collect->dropUntil(fn($v, $k) => null)->toArray();
    }

    public function testDropWhile(): void
    {
        // look at value
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $collect->dropWhile(fn($v) => $v < 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $collect->dropWhile(fn($v, $k) => $k !== 'c')->toArray());

        // drop until null does not work
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be bool. null given.');
        $collect->dropWhile(fn($v, $k) => null)->toArray();
    }

    public function testEach(): void
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

    public function testEachChunk(): void
    {
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        $collect->eachChunk(2, function (Collection $chunk, int $count) {
            if ($count === 0) {
                self::assertEquals(['a' => 1, 'b' => 2], $chunk->toArray());
            }
            if ($count === 1) {
                self::assertEquals(['c' => 3], $chunk->toArray());
            }
        });

        // chunk larger than assoc length
        $collect = $this->collect(['a' => 1]);
        $collect->eachChunk(2, function (Collection $chunk) {
            self::assertEquals(['a' => 1], $chunk->toArray());
        });
    }

    public function testEachChunk_NegativeValue(): void
    {
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be positive int. -2 given.');
        $collect->eachChunk(-2, fn() => null);
    }

    public function testEachWithIndex(): void
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

    public function testExcept(): void
    {
        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $collect->except(['a'])->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $collect->except(['a', 'c'])->toArray());
    }

    public function testFilter(): void
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

    public function testFirst(): void
    {
        $collect = $this->collect([10, 20]);
        self::assertEquals(10, $collect->first());
        self::assertEquals(20, $collect->first(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->first(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->first(fn() => false));
    }

    public function testFirstIndex(): void
    {
        $collect = $this->collect([10, 20, 20, 30]);
        self::assertEquals(2, $collect->firstIndex(fn($v, $k) => $k === 2));
        self::assertEquals(1, $collect->firstIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->firstIndex(fn() => false));
    }

    public function testFirstKey(): void
    {
        $collect = $this->collect([10, 20, 30]);
        self::assertEquals(1, $collect->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals(2, $collect->firstKey(fn($v, $k) => $k === 2));

        $collect = $this->collect(['a' => 10, 'b' => 20, 'c' => 30]);
        self::assertEquals('b', $collect->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals('c', $collect->firstKey(fn($v, $k) => $k === 'c'));
    }

    public function testFlatMap(): void
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, -1, 2, -2], $collect->flatMap(fn($i) => [$i, -$i])->toArray());

        $collect = $this->collect([['a'], ['b']]);
        self::assertEquals(['a', 'b'], $collect->flatMap(fn($a) => $a)->toArray());

        $collect = $this->collect([['a' => 1], [2], 2]);
        self::assertEquals([1, 2, 2], $collect->flatMap(fn($a) => $a)->toArray());
    }

    public function testFlatten(): void
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

    public function testFlatten_ZeroDepth(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be positive int. 0 given.');
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2], $collect->flatten(0)->toArray());
    }

    public function testFlatten_NegativeDepth(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be positive int. -1 given.');
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2], $collect->flatten(-1)->toArray());
    }

    public function testFlip(): void
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals([1 => 0, 2 => 1], $collect->flip()->toArray());

        $collect = $this->collect(['a' => 'b', 'c' => 'd']);
        self::assertEquals(['b' => 'a', 'd' => 'c'], $collect->flip()->toArray());
    }

    public function testGet(): void
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

    public function testGetIterator(): void
    {
        $iterator = $this->collect()->getIterator();
        self::assertEquals([], iterator_to_array($iterator));
    }

    public function testGroupBy(): void
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

    public function testImplode(): void
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

    public function testInsertAt(): void
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

    public function testIntersect(): void
    {
        $collect = $this->collect([1, 2, 3]);
        self::assertEquals([1], $collect->intersect([1])->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $collect->intersect([1])->toArray());

        $collect = $this->collect([]);
        self::assertEquals([], $collect->intersect([1])->toArray());
    }

    public function testIntersectKeys(): void
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

    public function testIsAssoc(): void
    {
        $collect = $this->collect([]);
        self::assertTrue($collect->isAssoc());

        $collect = $this->collect([1, 2]);
        self::assertFalse($collect->isAssoc());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->isAssoc());
    }

    public function testIsEmpty(): void
    {
        $collection = $this->collect([]);
        self::assertTrue($collection->isEmpty());

        $collection = $this->collect([1, 2]);
        self::assertFalse($collection->isEmpty());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertFalse($collect->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        $collect = $this->collect([]);
        self::assertFalse($collect->isNotEmpty());

        $collect = $this->collect([1, 2]);
        self::assertTrue($collect->isNotEmpty());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->isNotEmpty());
    }

    public function testIsList(): void
    {
        $collect = $this->collect([]);
        self::assertTrue($collect->isList());

        $collect = $this->collect([1, 2]);
        self::assertTrue($collect->isList());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertFalse($collect->isList());
    }

    public function testJsonSerialize(): void
    {
        $collect = $this->collect([]);
        self::assertEquals([], $collect->jsonSerialize());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->jsonSerialize());
    }

    public function testKeyBy(): void
    {
        $collect = $this->collect([1, 2])->keyBy(fn($v) => 'a'.$v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $collect->toArray());

        $collect = $this->collect([['id' => 'b'], ['id' => 'c']])->keyBy(fn($v) => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $collect->toArray());
    }

    public function testKeyBy_WithDuplicateKey(): void
    {
        $this->expectException(DuplicateKeyException::class);
        $this->collect([['id' => 'b'], ['id' => 'b']])->keyBy(fn($v) => $v['id']);
    }

    public function testKeyBy_WithOverwrittenKey(): void
    {
        $collect = $this->collect([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $collect->toArray());

        $this->expectException(DuplicateKeyException::class);
        $this->collect([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], false);
    }

    public function testKeyBy_WithInvalidKey(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->collect([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => false);
    }

    public function testKeys(): void
    {
        $keys = $this->collect([1,2])->keys();
        self::assertEquals([0,1], $keys->toArray());

        $keys = $this->collect(['a' => 1, 'b' => 2])->keys();
        self::assertEquals(['a', 'b'], $keys->toArray());
    }

    public function testLast(): void
    {
        $collect = $this->collect([10, 20]);
        self::assertEquals(20, $collect->last());
        self::assertEquals(20, $collect->last(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->last(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->last(fn() => false));
    }

    public function testLastIndex(): void
    {
        $collect = $this->collect([10, 20, 20]);
        self::assertEquals(1, $collect->lastIndex(fn($v, $k) => $k === 1));
        self::assertEquals(2, $collect->lastIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->lastIndex(fn() => false));
    }

    public function testLastKey(): void
    {
        $collect = $this->collect(['a' => 10, 'b' => 20, 'c' => 20]);
        self::assertEquals('c', $collect->lastKey());
        self::assertEquals('b', $collect->lastKey(fn($v, $k) => $k === 'b'));
        self::assertEquals('c', $collect->lastKey(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->lastKey(fn() => false));
    }

    public function testMacro(): void
    {
        Collection::macro('testMacro', fn($num) => $num * 100);
        $collect = $this->collect([1]);
        self::assertEquals(200, $collect->testMacro(2));
    }

    public function testMacroExists(): void
    {
        self::assertFalse(Collection::macroExists('testMacro2'));
        Collection::macro('testMacro2', fn() => 1);
        self::assertTrue(Collection::macroExists('testMacro2'));
    }

    public function testMap(): void
    {
        $collect = $this->collect([1, 2, 3]);
        self::assertEquals([2, 4, 6], $collect->map(fn($i) => $i * 2)->toArray());
        self::assertEquals([0, 1, 2], $collect->map(fn($i, $k) => $k)->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 2, 'b' => 4, 'c' => 6], $collect->map(fn($i) => $i * 2)->toArray());
    }

    public function testMax(): void
    {
        $collect = $this->collect([1, 2, 3, 10, 1]);
        self::assertEquals(10, $collect->max());

        $collect = $this->collect([100, 2, 3, 10, 1]);
        self::assertEquals(100, $collect->max());

        $collect = $this->collect([1, 2, 3, 10, 1, -100, 90]);
        self::assertEquals(90, $collect->max());
    }

    public function testMerge(): void
    {
        $empty = $this->collect();
        $merged = $empty->merge([1, [2]]);
        self::assertNotSame($empty, $merged);
        self::assertCount(0, $empty);
        self::assertEquals([1, [2]], $merged->toArray());

        $empty = $this->collect();
        $merged = $empty->merge([1, [2]]);
        self::assertEquals([1, [2]], $merged->toArray());

        $empty = $this->collect(['0' => 1]);
        $merged = $empty->merge([1, [2]]);
        self::assertEquals(['0' => 1, 1, [2]], $merged->toArray());

        $assoc = $this->collect([1, 'a' => [1, 2]]);
        $merged = $assoc->merge([1, 'a' => [3]]);
        self::assertSame([1, 'a' => [3], 1], $merged->toArray());

        $assoc = $this->collect([1, 'a' => [1, 2], 2]);
        $merged = $assoc->merge(['a' => [3], 3]);
        self::assertSame([1, 'a' => [3], 2, 3], $merged->toArray());
    }

    public function testMergeRecursive(): void
    {
        $collect = $this->collect([])->mergeRecursive([]);
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 2])->mergeRecursive([3]);
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = $this->collect(['a' => 1])->mergeRecursive(['a' => 2]);
        self::assertEquals(['a' => 2], $collect->toArray());

        $collect = $this->collect(['a' => 1])->mergeRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 2, 'b' => 2], $collect->toArray());

        $collect = $this->collect(['a' => 1])->mergeRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->collect(['a' => 1])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => ['c' => 1]], $collect->toArray());

        $collect = $this->collect(['a' => [1,2]])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $collect->toArray());

        $collect = $this->collect(['a' => ['b' => 1], 'd' => 4])->mergeRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $collect->toArray());
    }

    public function testMin(): void
    {
        $collect = $this->collect([1, 2, 3, 10, -1]);
        self::assertEquals(-1, $collect->min());

        $collect = $this->collect([0, -1]);
        self::assertEquals(-1, $collect->min());

        $collect = $this->collect([1, 10, -100]);
        self::assertEquals(-100, $collect->min());
    }

    public function testMinMax(): void
    {
        $collect = $this->collect([]);
        self::assertEquals([null, null], $collect->minMax());

        $collect = $this->collect([1]);
        self::assertEquals([1, 1], $collect->minMax());

        $collect = $this->collect([1, 10, -100]);
        self::assertEquals([-100, 10], $collect->minMax());
    }

    public function testNewInstance(): void
    {
        $collect = $this->collect([]);
        self::assertNotSame($collect, $collect->newInstance());
        self::assertEquals($collect, $collect->newInstance());

        $collect = $this->collect([1, 10]);
        self::assertEquals([], $collect->newInstance()->toArray());
    }

    public function testNotContains(): void
    {
        self::assertTrue($this->collect([])->notContains(0));
        self::assertTrue($this->collect([])->notContains(null));
        self::assertTrue($this->collect([])->notContains([]));
        self::assertTrue($this->collect([null, 0])->notContains(false));
        self::assertTrue($this->collect([null, 0])->notContains(1));
        self::assertTrue($this->collect(['a' => 1])->notContains('a'));
        self::assertFalse($this->collect([null, 0])->notContains(null));
        self::assertFalse($this->collect([null, []])->notContains([]));
        self::assertFalse($this->collect(['a' => 1, 0])->notContains(1));
    }

    public function testNotContainsKey(): void
    {
        self::assertTrue($this->collect([])->notContainsKey(0));
        self::assertTrue($this->collect([])->notContainsKey(1));
        self::assertTrue($this->collect(['b' => 1])->notContainsKey('a'));
        self::assertFalse($this->collect([1])->notContainsKey(0));
        self::assertFalse($this->collect([11 => 1])->notContainsKey(11));
        self::assertFalse($this->collect(['a' => 1, 0])->notContainsKey('a'));
    }

    public function testNotEquals(): void
    {
        self::assertTrue($this->collect([])->notEquals($this->collect([1])));
        self::assertTrue($this->collect([])->notEquals($this->collect([null])));
        self::assertTrue($this->collect(['b' => 1])->notEquals($this->collect(['a' => 1])));
        self::assertFalse($this->collect([1])->notEquals($this->collect([1])));
        self::assertFalse($this->collect(['a' => 1])->notEquals($this->collect(['a' => 1])));
    }

    public function testOffsetExists(): void
    {
        $seq = $this->collect([1, 2]);
        self::assertTrue(isset($seq[0]));

        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue(isset($assoc['b']));

        $assoc = $this->collect([]);
        self::assertFalse(isset($assoc['a']));
    }

    public function testOffsetGet(): void
    {
        $seq = $this->collect([1, 2]);
        self::assertEquals(1, $seq[0]);

        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $assoc['b']);
    }

    public function testOffsetGet_UndefinedKey(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "e"');
        $this->collect(['a' => 1, 'b' => 2])['e'];
    }

    public function testOffsetSet(): void
    {
        // push number
        $seq = $this->collect([1, 2]);
        $seq[] = 3;
        self::assertEquals([1, 2, 3], $seq->toArray());

        // skip number from 0, 1, to 3
        $seq = $this->collect([1, 2]);
        $seq[3] = 3;
        self::assertEquals([1, 2, 3 => 3], $seq->toArray());

        // set offset with string
        $assoc = $this->collect(['a' => 1, 'b' => 2]);
        $assoc['c'] = 3;
        self::assertEquals(3, $assoc['c']);
    }

    public function testOffsetSet_BoolAsKey(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->collect([])[true]= 1;
    }

    public function testOffsetSet_FloatAsKey(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->collect([])[1.1]= 1;
    }

    public function testOffsetUnset(): void
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

    public function testOnly(): void
    {
        // with list array
        $collect = $this->collect([1, 2, 3]);
        self::assertEquals([1 => 2], $collect->only([1])->toArray());

        // with assoc array
        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->only(['a', 'b'])->toArray());

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], $collect->only(['c', 'b'])->toArray());

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], $collect->only(['x' => 'c', 'b'])->toArray());
    }

    public function testOnly_WithUndefinedKey(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "a"');
        self::assertEquals([], $this->collect()->only(['a'])->toArray());
    }

    public function testPad(): void
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals([1, 2], $collect->pad(0, 9)->toArray());
        self::assertEquals([1, 2], $collect->pad(2, 9)->toArray());
        self::assertEquals([1, 2], $collect->pad(-1, 9)->toArray());
        self::assertEquals([1, 2, 9], $collect->pad(3, 9)->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2, 0 => 9], $collect->pad(3, 9)->toArray());

        self::assertEquals([9, 9, 9], $this->collect()->pad(3, 9)->toArray());
    }

    public function testPop(): void
    {
        $collect = $this->collect([1, 2]);
        self::assertEquals(2, $collect->pop());
        self::assertEquals([1], $collect->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $collect->pop());
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function testPull(): void
    {
        $collect = $this->collect();
        self::assertEquals(null, $collect->pull(1));

        $collect = $this->collect([1, 2]);
        self::assertEquals(2, $collect->pull(1));
        self::assertEquals([1], $collect->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $collect->pull('b'));
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->collect(['a' => ['id' => 1], 'b' => 2]);
        self::assertEquals(1, $collect->pull('a.id'));
        self::assertEquals(['a' => [], 'b' => 2], $collect->toArray());
    }

    public function testPush(): void
    {
        $collect = $this->collect([1, 2]);
        self::assertSame($collect, $collect->push(3));
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = $this->collect([1, 2]);
        self::assertSame($collect, $collect->push(3));
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertSame($collect, $collect->push('b'));
        self::assertEquals(['a' => 1, 'b' => 2, 'b'], $collect->toArray());
    }

    public function testReduce(): void
    {
        /** @var Collection $reduced */
        $reduced = $this->collect()->reduce(fn(Collection $c) => $c->push(1));
        self::assertEquals([], $reduced->toArray());

        $reduced = $this->collect(['a' => 1])->reduce(fn(Collection $c, $i, $k) => $c->set($k, $i * 2));
        self::assertEquals(['a' => 2], $reduced->toArray());

        $reduced = $this->collect(['a' => 1, 'b' => 2])->reduce(fn(Collection $c, $i, $k) => $c->set($k, $i * 2));
        self::assertEquals(['a' => 2, 'b' => 4], $reduced->toArray());

        $reduced = $this->collect(['a' => 1, 'b' => 2])->reduce(fn($c, $i, $k) => tap($c, static fn($c) => $c->$k = 0), (object)[]);
        self::assertEquals(['a' => 0, 'b' => 0], (array) $reduced);

        $reduced = $this->collect([1, 2, 3])->reduce(fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function testReduce_UnableToGuessInitial(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Initial value not set and not guessable.');
        $this->collect([1, 2, 3])->reduce(fn($c, $i, $k) => $c + $i);
    }

    public function testRemove(): void
    {
        $collect = $this->collect();
        self::assertEquals(0, $collect->remove(1));

        $collect = $this->collect([1]);
        self::assertEquals(1, $collect->remove(1));
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 1]);
        self::assertEquals(2, $collect->remove(1));
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 1]);
        self::assertEquals(1, $collect->remove(1, 1));
        self::assertEquals([1 => 1], $collect->toArray());

        $collect = $this->collect(['a' => 1]);
        self::assertEquals(1, $collect->remove(1));
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect(['a' => 1]);
        self::assertEquals(0, $collect->remove(1, -1));
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function testRemoveKey(): void
    {
        $collect = $this->collect();
        self::assertEquals(false, $collect->removeKey(1));

        $collect = $this->collect([1]);
        self::assertEquals(true, $collect->removeKey(0));
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect(['a' => 1]);
        self::assertEquals(true, $collect->removeKey('a'));
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect(['a' => 1]);
        self::assertEquals(false, $collect->removeKey('b'));
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->collect(['a' => ['id' => 1], 1]);
        self::assertEquals(true, $collect->removeKey('a.id'));
        self::assertEquals(false, $collect->removeKey('1.a'));
        self::assertEquals(['a' => [], 1], $collect->toArray());
    }

    public function testReverse(): void
    {
        $collect = $this->collect([])->reverse();
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 2])->reverse();
        self::assertEquals([2, 1], $collect->toArray());

        $collect = $this->collect([100 => 1, 200 => 2])->reverse();
        self::assertEquals([200 => 2, 100 => 1], $collect->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 3])->reverse();
        self::assertEquals([3, 'b' => 2, 'a' => 1], $collect->toArray());

        $collect = $this->collect(['a' => 1, 2, 3, 4])->reverse();
        self::assertEquals([2 => 4, 1 => 3, 0 => 2, 'a' => 1], $collect->toArray());
    }

    public function testSample(): void
    {
        mt_srand(100);
        self::assertEquals(8, $this->collect(range(0, 10))->sample());
    }

    public function testSample_Empty(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('array_rand(): Argument #1 ($array) cannot be empty');
        $this->collect()->sample();
    }

    public function testSampleMany(): void
    {
        mt_srand(100);
        self::assertEquals([8 => 8, 9 => 9], $this->collect(range(0, 10))->sampleMany(2)->toArray());
    }

    public function testSatisfyAll(): void
    {
        $collect = $this->collect([]);
        self::assertTrue($collect->satisfyAll(static fn($v) => is_int($v)));

        $collect = $this->collect([1, 2, 3]);
        self::assertTrue($collect->satisfyAll(static fn($v) => is_int($v)));

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertTrue($collect->satisfyAll(static fn($v, $k) => is_string($k)));

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 3, 4]);
        self::assertFalse($collect->satisfyAll(static fn($k) => is_string($k)));
    }

    public function testSatisfyAny(): void
    {
        $empty = $this->collect();
        self::assertFalse($empty->satisfyAny(static fn() => true));

        $collect = $this->collect([1, null, 2, [3], false]);
        self::assertTrue($collect->satisfyAny(static fn($v) => true));
        self::assertFalse($collect->satisfyAny(static fn($v) => false));
        self::assertTrue($collect->satisfyAny(static fn($v) => is_array($v)));

        $collect = $this->collect(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->satisfyAny(static fn($v, $k) => true));
        self::assertFalse($collect->satisfyAny(static fn($v) => false));
        self::assertTrue($collect->satisfyAny(static fn($v, $k) => $k === 'b'));
    }

    public function testSet(): void
    {
        self::assertEquals(['a' => 1], $this->collect()->set('a', 1)->toArray());
        self::assertEquals(['a' => 1], $this->collect()->set('a', 0)->set('a', 1)->toArray());
        self::assertEquals(['a' => null], $this->collect()->set('a', null)->toArray());
        self::assertEquals(['a' => ['b' => 1]], $this->collect()->set('a.b', 1)->toArray());
    }

    public function testSetIfNotExists(): void
    {
        self::assertEquals(['a' => 1], $this->collect()->setIfNotExists('a', 1)->toArray());
        self::assertEquals(['a' => 0], $this->collect()->setIfNotExists('a', 0)->setIfNotExists('a', 1)->toArray());
        self::assertEquals(['a' => null], $this->collect()->setIfNotExists('a', null)->toArray());
        self::assertEquals(['a' => ['b' => 1]], $this->collect(['a' => ['b' => 1]])->setIfNotExists('a.b', 0)->toArray());
    }

    public function testShift(): void
    {
        self::assertEquals(1, $this->collect([1, 2])->shift());
        self::assertEquals(null, $this->collect()->shift());
        self::assertEquals(1, $this->collect(['a' => 1, 2])->shift());
        self::assertEquals(['b' => 1], $this->collect(['a' => ['b' => 1]])->shift());
    }

    public function testShuffle(): void
    {
        mt_srand(100);
        self::assertEquals([1, 2, 4, 3, 2], $this->collect([1, 2, 2, 3, 4])->shuffle()->toArray());
        self::assertSame(['a' => 1, 'c' => 3, 'b' => 2, 'd' => 4], $this->collect(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->shuffle()->toArray());
    }

    public function testSlice(): void
    {
        $collect = $this->collect([1, 2, 3])->slice(1);
        self::assertEquals([2, 3], $collect->toArray());

        $collect = $this->collect([1, 2, 3])->slice(0, -1);
        self::assertEquals([1, 2], $collect->toArray());
    }

    public function testSortBy(): void
    {
        $collect = $this->collect([4, 2, 1, 3])->sortBy(fn($v) => $v)->values();
        self::assertEquals([1, 2, 3, 4], $collect->toArray());

        $collect = $this->collect(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $collect->toArray());
    }

    public function testSortByDesc(): void
    {
        $collect = $this->collect([4, 2, 1, 3])->sortByDesc(fn($v) => $v)->values();
        self::assertEquals([4, 3, 2, 1], $collect->toArray());

        $collect = $this->collect(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $collect->toArray());
    }

    public function testSortKeys(): void
    {
        $collect = $this->collect(['b' => 0, 'a' => 1, 'c' => 2])->sortKeys();
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $collect->toArray());

        $collect = $this->collect(['2' => 0, '100' => 1, '30' => 2])->sortKeys(SORT_NATURAL);
        self::assertEquals(['2' => 0, '30' => 2, '100' => 1], $collect->toArray());
    }

    public function testSortValues(): void
    {
        $collect = $this->collect([4, 2, 1, 3])->sortValues()->values();
        self::assertEquals([1, 2, 3, 4], $collect->toArray());

        $collect = $this->collect(['30', '2', '100'])->sortValues(SORT_NATURAL)->values();
        self::assertEquals(['2', '30', '100'], $collect->toArray());

        $collect = $this->collect(['a' => 3, 'b' => 1, 'c' => 2])->sortValues();
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $collect->toArray());
    }

    public function testSortValuesDesc(): void
    {
        $collect = $this->collect([4, 2, 1, 3])->sortValuesDesc()->values();
        self::assertEquals([4, 3, 2, 1], $collect->toArray());

        $collect = $this->collect(['30', '100', '2'])->sortValuesDesc(SORT_NATURAL)->values();
        self::assertEquals(['100', '30', '2'], $collect->toArray());

        $collect = $this->collect(['a' => 3, 'b' => 1, 'c' => 2])->sortValuesDesc();
        self::assertEquals(['a' => 3, 'c' => 2, 'b' => 1], $collect->toArray());
    }

    public function testSortWith(): void
    {
        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->sortWith(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $collect->toArray());
    }

    public function testSum(): void
    {
        $sum = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->sum();
        self::assertEquals(6, $sum);

        $sum = $this->collect([1, 1, 1])->sum();
        self::assertEquals(3, $sum);

        $sum = $this->collect([0.1, 0.2])->sum();
        self::assertEquals(0.3, $sum);

        $sum = $this->collect([])->sum();
        self::assertEquals(0, $sum);
    }

    public function testSum_ThrowOnSumOfString(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unsupported operand types: int + string');
        $this->collect(['a', 'b'])->sum();
    }

    public function testTake(): void
    {
        $collect = $this->collect([2, 3, 4])->take(2);
        self::assertEquals([2, 3], $collect->toArray());

        $collect = $this->collect([2, 3, 4])->take(-1);
        self::assertEquals([4], $collect->toArray());

        $collect = $this->collect([2, 3, 4])->take(0);
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->take(1);
        self::assertEquals(['b' => 1], $collect->toArray());

    }

    public function testTakeUntil(): void
    {
        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => $v > 2);
        self::assertEquals(['b' => 1], $collect->toArray());

        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => false);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $collect->toArray());

        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => true);
        self::assertEquals([], $collect->toArray());
    }

    public function testTakeWhile(): void
    {
        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 4])->takeWhile(fn($v) => $v < 4);
        self::assertEquals(['b' => 1, 'a' => 3], $collect->toArray());

        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => false);
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => true);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $collect->toArray());
    }

    public function testTally(): void
    {
        $collect = $this->collect([1, 1, 1, 2, 3, 3])->tally();
        self::assertEquals([1 => 3, 2 => 1, 3 => 2], $collect->toArray());

        $collect = $this->collect(['b' => 1, 'a' => 1, 'c' => 1])->tally();
        self::assertEquals([1 => 3], $collect->toArray());
    }

    public function testTap(): void
    {
        $collect = $this->collect([1, 2])->tap(fn() => 100);
        self::assertEquals([1, 2], $collect->toArray());

        $cnt = 0;
        $collect = $this->collect([])->tap(function() use (&$cnt) { $cnt+= 1; });
        self::assertEquals([], $collect->toArray());
        self::assertEquals(1, $cnt);
    }

    public function testToArray(): void
    {
        self::assertEquals([], $this->collect()->toArray());
        self::assertEquals([1, 2], $this->collect([1, 2])->toArray());
        self::assertEquals(['a' => 1], $this->collect(['a' => 1])->toArray());

        $inner = $this->collect([1, 2]);
        self::assertEquals(['a' => $inner], $this->collect(['a' => $inner])->toArray());
    }

    public function testToArrayRecursive(): void
    {
        // no depth defined
        $inner = $this->collect([1, 2]);
        $array = $this->collect(['a' => $inner])->toArrayRecursive();
        self::assertEquals(['a' => [1, 2]], $array);

        // test each depth
        $inner1 = $this->collect([1]);
        $inner2 = $this->collect([2, 3, $inner1]);
        $collect = $this->collect(['a' => $inner2]);
        self::assertEquals(['a' => $inner2], $collect->toArrayRecursive(1));
        self::assertEquals(['a' => [2, 3, $inner1]], $collect->toArrayRecursive(2));
        self::assertEquals(['a' => [2, 3, [1]]], $collect->toArrayRecursive(3));
    }

    public function testToJson(): void
    {
        $json = $this->collect([1, 2])->toJson();
        self::assertEquals("[1,2]", $json);

        $json = $this->collect(['a' => 1, 'b' => 2])->toJson();
        self::assertEquals("{\"a\":1,\"b\":2}", $json);

        $json = $this->collect(["あ"])->toJson();
        self::assertEquals("[\"あ\"]", $json);

        $json = $this->collect([1])->toJson(JSON_PRETTY_PRINT);
        self::assertEquals("[\n    1\n]", $json);
    }

    public function testToUrlQuery(): void
    {
        $query = $this->collect(['a' => 1])->toUrlQuery('t');
        self::assertEquals(urlencode('t[a]').'=1', $query);

        $query = $this->collect(['a' => 1, 'b' => 2])->toUrlQuery();
        self::assertEquals("a=1&b=2", $query);
    }

    public function testUnion(): void
    {
        $collect = $this->collect([])->union([]);
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect(['a' => 1])->union(['a' => 2]);
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->collect(['a' => ['b' => 1]])->union(['a' => ['c' => 2]]);
        self::assertEquals(['a' => ['b' => 1]], $collect->toArray());
    }

    public function testUnionRecursive(): void
    {
        $collect = $this->collect([])->unionRecursive([]);
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 2])->unionRecursive([3]);
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = $this->collect(['a' => 1])->unionRecursive(['a' => 2]);
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->collect(['a' => 1])->unionRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->collect(['a' => 1])->unionRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->collect(['a' => 1])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->collect(['a' => [1,2]])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $collect->toArray());

        $collect = $this->collect(['a' => ['b' => 1], 'd' => 4])->unionRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $collect->toArray());
    }

    public function testUnique(): void
    {
        $collect = $this->collect([])->unique();
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 1, 2, 2])->unique();
        self::assertEquals([0 => 1, 2 => 2], $collect->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2, 'c' => 2])->unique();
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());
    }

    public function testUnshift(): void
    {
        $collect = $this->collect([])->unshift(1);
        self::assertEquals([1], $collect->toArray());

        $collect = $this->collect([1, 1])->unshift(0);
        self::assertEquals([0, 1, 1], $collect->toArray());

        $collect = $this->collect(['a' => 1])->unshift(1, 2);
        self::assertEquals([1, 2, 'a' => 1], $collect->toArray());
    }

    public function testValues(): void
    {
        $collect = $this->collect([])->values();
        self::assertEquals([], $collect->toArray());

        $collect = $this->collect([1, 1, 2])->values()->reverse();
        self::assertEquals([2, 1, 1], $collect->toArray());

        $collect = $this->collect(['a' => 1, 'b' => 2])->values();
        self::assertEquals([1, 2], $collect->toArray());
    }
}
