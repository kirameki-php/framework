<?php declare(strict_types=1);

namespace Tests\Kirameki\Support;

use DivisionByZeroError;
use ErrorException;
use InvalidArgumentException;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidKeyException;
use Kirameki\Exception\InvalidValueException;
use Kirameki\Support\Collection;
use Kirameki\Support\Sequence;
use RuntimeException;
use Tests\Kirameki\TestCase;
use TypeError;
use ValueError;
use function collect;

class SequenceTest extends TestCase
{
    /**
     * @template TKey as array-key
     * @template TValue
     * @param array<TKey, TValue> $items
     * @return Sequence<TKey, TValue>
     */
    protected function seq(?array $items = null): Sequence
    {
        return new Sequence($items ?? []);
    }

    public function test__Construct(): void
    {
        // empty
        $empty = new Sequence();
        self::assertEquals([], $empty->toArray());

        // ordinal
        $ordinal = new Sequence([1, 2]);
        self::assertEquals([1, 2], $ordinal->toArray());

        // assoc
        $assoc = new Sequence(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $assoc->toArray());

        // array in collection
        $inner = new Sequence([1]);
        $seq = new Sequence([$inner]);
        self::assertEquals([$inner], $seq->toArray());

        // iterable in collection
        $seq = new Sequence(new Sequence([1, 2]));
        self::assertEquals([1, 2], $seq->toArray());
    }

    public function test__Construct_BadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument #1 ($items) must be of type ?iterable, int given');
        new Sequence(1);
    }

    public function testAt(): void
    {
        self::assertEquals(null, $this->seq()->at(0));
        self::assertEquals(null, $this->seq([1, 2, 3])->at(5));
        self::assertEquals(null, $this->seq([1, 2, 3])->at(PHP_INT_MIN));
        self::assertEquals(null, $this->seq([1, 2, 3])->at(PHP_INT_MAX));

        self::assertEquals(1, $this->seq([1, 2, 3])->at(0));
        self::assertEquals(2, $this->seq([1, 2, 3])->at(1));
        self::assertEquals(3, $this->seq([1, 2, 3])->at(-1));

        self::assertEquals(1, $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->at(0));
        self::assertEquals(2, $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->at(1));
        self::assertEquals(3, $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->at(-1));
    }

    public function testAverage(): void
    {
        $average = $this->seq([])->average();
        self::assertEquals(0, $average);

        $average = $this->seq([1, 2])->average(allowEmpty: false);
        self::assertEquals(1.5, $average);

        $average = $this->seq([1, 2])->average();
        self::assertEquals(1.5, $average);

        $average = $this->seq([1, 2, 3])->average();
        self::assertEquals(2, $average);

        $average = $this->seq([0, 0, 0])->average();
        self::assertEquals(0, $average);
    }

    public function testAverage_NotEmpty(): void
    {
        $this->expectException(DivisionByZeroError::class);
        $this->seq([])->average(allowEmpty: false);
    }

    public function testChunk(): void
    {
        // empty but not same instance
        $empty = $this->seq();
        $result = $empty->chunk(1);
        self::assertEmpty($result);
        self::assertNotSame($empty, $result);

        $seq = $this->seq([1, 2, 3]);

        $chunked = $seq->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals([1, 2], $chunked->first()->toArray());
        self::assertEquals([3], $chunked->last()->toArray());

        // size larger than items -> returns everything
        $chunked = $seq->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals([1, 2, 3], $chunked->first()->toArray());
        self::assertNotSame($chunked, $seq);

        $assoc = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);

        // test preserveKeys: true
        $chunked = $assoc->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2], $chunked->first()->toArray());
        self::assertEquals(['c' => 3], $chunked->last()->toArray());

        // size larger than items -> returns everything
        $chunked = $assoc->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $chunked->first()->toArray());
        self::assertNotSame($chunked, $assoc);
    }

    public function testChunkInvalidSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $this->seq([1])->chunk(0);
    }

    public function testCoalesce(): void
    {
        $result = $this->seq()->coalesce();
        self::assertNull($result);

        $result = $this->seq([null, 0, 1])->coalesce();
        self::assertEquals(0, $result);

        $result = $this->seq([0, null, 1])->coalesce();
        self::assertEquals(0, $result);

        $result = $this->seq(['', null, 1])->coalesce();
        self::assertEquals('', $result);

        $result = $this->seq(['', null, 1])->coalesce();
        self::assertEquals('', $result);

        $result = $this->seq([[], null, 1])->coalesce();
        self::assertEquals([], $result);

        $result = $this->seq([null, [], 1])->coalesce();
        self::assertEquals([], $result);

        $result = $this->seq([null, null, 1])->coalesce();
        self::assertEquals(1, $result);
    }

    public function testCoalesceOrFail_Empty(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be not null. null given.');
        $this->seq([])->coalesceOrFail();
    }

    public function testCoalesceOrFail_OnlyNull(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be not null. null given.');
        $this->seq([null])->coalesceOrFail();
    }

    public function testCompact(): void
    {
        // empty but not same instance
        $empty = $this->seq();
        self::assertNotSame($empty, $empty->compact());

        // sequence: removes nulls
        $compacted = $this->seq([1, null, null, 2])->compact();
        self::assertCount(2, $compacted);
        self::assertEquals([0 => 1, 3 => 2], $compacted->toArray());

        // sequence: no nulls
        $seq = $this->seq([1, 2]);
        $compacted = $seq->compact();
        self::assertNotSame($seq, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals([0 => 1, 1 => 2], $compacted->toArray());

        // sequence: all nulls
        $compacted = $this->seq([null, null])->compact();
        self::assertEmpty($compacted->toArray());
        self::assertEquals([], $compacted->toArray());

        // assoc: removes nulls
        $assoc = $this->seq(['a' => null, 'b' => 1, 'c' => 2, 'd' => null]);
        $compacted = $assoc->compact();
        self::assertCount(2, $compacted);
        self::assertEquals(['b' => 1, 'c' => 2], $compacted->toArray());

        // assoc: no nulls
        $assoc = $this->seq(['a' => 1, 'b' => 2]);
        $compacted = $assoc->compact();
        self::assertNotSame($assoc, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals(['a' => 1, 'b' => 2], $compacted->toArray());

        // assoc: all nulls
        $compacted = $this->seq(['a' => null, 'b' => null])->compact();
        self::assertEmpty($compacted->toArray());
        self::assertEquals([], $compacted->toArray());

        // depth = INT_MAX
        $compacted = $this->seq(['a' => ['b' => ['c' => null]], 'b' => null])->compact(PHP_INT_MAX);
        self::assertEquals(['a' => ['b' => []]], $compacted->toArray());

        // depth = 1
        $compacted = $this->seq(['a' => ['b' => null], 'b' => null])->compact();
        self::assertEquals(['a' => ['b' => null]], $compacted->toArray());
    }

    public function testContains(): void
    {
        $empty = $this->seq();
        self::assertFalse($empty->contains(null));

        // sequence: compared with value
        $collect = $this->seq([1, null, 2, [3], false]);
        self::assertTrue($collect->contains(1));
        self::assertTrue($collect->contains(null));
        self::assertTrue($collect->contains([3]));
        self::assertTrue($collect->contains(false));
        self::assertFalse($collect->contains(3));
        self::assertFalse($collect->contains([]));

        // assoc: compared with value
        $collect = $this->seq(['a' => 1]);
        self::assertTrue($collect->contains(1));
        self::assertFalse($collect->contains(['a' => 1]));
        self::assertFalse($collect->contains(['a']));
    }

    public function testContainsKey(): void
    {
        // empty but not same instance
        $empty = $this->seq();
        self::assertFalse($empty->containsKey('a'));
        self::assertEmpty($empty->containsKey(0));
        self::assertEmpty($empty->containsKey(-1));

        // copy sequence
        $seq = $this->seq([-2 => 1, 3, 4, [1, 2, [1, 2, 3]], [null]]);
        self::assertTrue($seq->containsKey(1));
        self::assertTrue($seq->containsKey('1'));
        self::assertTrue($seq->containsKey('-2'));
        self::assertTrue($seq->containsKey(-2));
        self::assertTrue($seq->containsKey(-1));
        self::assertFalse($seq->containsKey(999));
        self::assertFalse($seq->containsKey('0.3'));
        self::assertTrue($seq->containsKey("2"));

        // copy assoc
        $assoc = $this->seq(['a' => [1, 2, 3], '-' => 'c', 'd' => ['e'], 'f' => null]);
        self::assertTrue($assoc->containsKey('a'));
        self::assertFalse($assoc->containsKey('a.a'));
        self::assertTrue($assoc->containsKey('f'));
    }

    public function testCopy(): void
    {
        // empty but not same instance
        $empty = $this->seq();
        $clone = $empty->copy();
        self::assertNotSame($empty, $clone);
        self::assertEmpty($clone);

        // copy sequence
        $seq = $this->seq([3, 4]);
        $clone = $seq->copy();
        self::assertNotSame($seq, $clone);
        self::assertEquals([3, 4], $seq->toArray());

        // copy assoc
        $seq = $this->seq(['a' => 3, 'b' => 4]);
        $clone = $seq->copy();
        self::assertNotSame($seq, $clone);
        self::assertEquals(['a' => 3, 'b' => 4], $seq->toArray());
    }

    public function testCount(): void
    {
        // empty
        $empty = $this->seq();
        self::assertEquals(0, $empty->count());

        // count default
        $simple = $this->seq([1, 2, 3]);
        self::assertEquals(3, $simple->count());
    }

    public function testCountBy(): void
    {
        $simple = $this->seq([1, 2, 3]);
        self::assertEquals(2, $simple->countBy(fn($v) => $v > 1));
    }

    public function testDiff(): void
    {
        $empty = $this->seq();
        $diffed = $empty->diff([1]);
        self::assertNotSame($empty, $diffed);
        self::assertCount(0, $empty);
        self::assertCount(0, $diffed);

        $original = [-1, 'a' => 1, 'b' => 2, 3];
        $differ = [2, 3, 'a' => 1, 'c' => 2, 5];
        $assoc = $this->seq($original);
        $diffed = $assoc->diff($differ);
        self::assertNotSame($assoc, $diffed);
        self::assertSame($original, $assoc->toArray());
        self::assertSame([-1], $diffed->toArray());
    }

    public function testDiffKeys(): void
    {
        $empty = $this->seq();
        $diffed = $empty->diffKeys([-1]);
        self::assertNotSame($empty, $diffed);
        self::assertCount(0, $empty);
        self::assertCount(0, $diffed);

        $original = [-1, 'a' => 1, 'b' => 2, 3, -10 => -10];
        $differ = [2, 3, 'a' => 1, 'c' => 2, 5];
        $assoc = $this->seq($original);
        $diffed = $assoc->diffKeys($differ);
        self::assertNotSame($assoc, $diffed);
        self::assertSame($original, $assoc->toArray());
        self::assertSame(['b' => 2, -10 => -10], $diffed->toArray());
    }

    public function testDrop(): void
    {
        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['b' => 2, 'c' => 3], $collect->drop(1)->toArray());

        // over value
        $collect = $this->seq(['a' => 1]);
        self::assertEquals([], $collect->drop(2)->toArray());

        // negative
        $collect = $this->seq(['a' => 1, 'b' => 1]);
        self::assertEquals(['a' => 1], $collect->drop(-1)->toArray());

        // zero
        $collect = $this->seq(['a' => 1]);
        self::assertEquals(['a' => 1], $collect->drop(0)->toArray());
    }

    public function testDropUntil(): void
    {
        // look at value
        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $collect->dropUntil(fn($v) => $v >= 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $collect->dropUntil(fn($v, $k) => $k === 'c')->toArray());

        // drop until null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Kirameki\Support\Arr::verify(): Return value must be of type bool, null returned');
        $collect->dropUntil(fn($v, $k) => null)->toArray();
    }

    public function testDropWhile(): void
    {
        // look at value
        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $collect->dropWhile(fn($v) => $v < 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $collect->dropWhile(fn($v, $k) => $k !== 'c')->toArray());

        // drop until null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Kirameki\Support\Arr::verify(): Return value must be of type bool, null returned');
        $collect->dropWhile(fn($v, $k) => null)->toArray();
    }

    public function testEach(): void
    {
        $collect = $this->seq(['a' => 1, 'b' => 2]);
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

    public function testEachWithIndex(): void
    {
        $collect = $this->seq(['a' => 1, 'b' => 2]);
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
        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $collect->except(['a'])->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $collect->except(['a', 'c'])->toArray());
    }

    public function testFilter(): void
    {
        // sequence: remove ones with empty value
        $collect = $this->seq([0, 1, '', '0', null]);
        self::assertEquals([1 => 1], $collect->filter(fn($item) => !empty($item))->toArray());

        // assoc: removes null / false / 0 / empty string / empty array
        $collect = $this->seq(['a' => null, 'b' => false, 'c' => 0, 'd' => '', 'e' => '0', 'f' => []]);
        self::assertEquals([], $collect->filter(fn($item) => !empty($item))->toArray());

        // assoc: removes ones with condition
        self::assertEquals(['d' => ''], $collect->filter(fn($v) => $v === '')->toArray());
    }

    public function testFirst(): void
    {
        $collect = $this->seq([10, 20]);
        self::assertEquals(10, $collect->first());
        self::assertEquals(20, $collect->first(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->first(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->first(fn() => false));
    }

    public function testFirstIndex(): void
    {
        $collect = $this->seq([10, 20, 20, 30]);
        self::assertEquals(2, $collect->firstIndex(fn($v, $k) => $k === 2));
        self::assertEquals(1, $collect->firstIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->firstIndex(fn() => false));
    }

    public function testFirstKey(): void
    {
        $collect = $this->seq([10, 20, 30]);
        self::assertEquals(1, $collect->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals(2, $collect->firstKey(fn($v, $k) => $k === 2));

        $collect = $this->seq(['a' => 10, 'b' => 20, 'c' => 30]);
        self::assertEquals('b', $collect->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals('c', $collect->firstKey(fn($v, $k) => $k === 'c'));
    }

    public function testFirstOrFail(): void
    {
        $collect = $this->seq([10, 20]);
        self::assertEquals(10, $collect->firstOrFail());
        self::assertEquals(20, $collect->firstOrFail(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->firstOrFail(fn($v, $k) => $v === 20));
    }

    public function testFirstOrFail_Empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->firstOrFail();
    }

    public function testFirstOrFail_BadCondition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->firstOrFail(fn(int $i) => $i > 2);
    }

    public function testFlatMap(): void
    {
        $collect = $this->seq([1, 2]);
        self::assertEquals([1, -1, 2, -2], $collect->flatMap(fn($i) => [$i, -$i])->toArray());

        $collect = $this->seq([['a'], ['b']]);
        self::assertEquals(['a', 'b'], $collect->flatMap(fn($a) => $a)->toArray());

        $collect = $this->seq([['a' => 1], [2], 2]);
        self::assertEquals([1, 2, 2], $collect->flatMap(fn($a) => $a)->toArray());
    }

    public function testFlatten(): void
    {
        // nothing to flatten
        $collect = $this->seq([1, 2]);
        self::assertEquals([1, 2], $collect->flatten()->toArray());

        // flatten only 1 as default
        $collect = $this->seq([[1, [2, 2]], 3]);
        self::assertEquals([1, [2, 2], 3], $collect->flatten()->toArray());

        // flatten more than 1
        $collect = $this->seq([['a' => 1], [1, [2, [3, 3], 2], 1]]);
        self::assertEquals([1, 1, 2, [3, 3], 2, 1], $collect->flatten(2)->toArray());

        // assoc info is lost
        $collect = $this->seq([['a'], 'b', ['c' => 'd']]);
        self::assertEquals(['a', 'b', 'd'], $collect->flatten()->toArray());
    }

    public function testFlatten_ZeroDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $collect = $this->seq([1, 2]);
        self::assertEquals([1, 2], $collect->flatten(0)->toArray());
    }

    public function testFlatten_NegativeDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: -1');
        $collect = $this->seq([1, 2]);
        self::assertEquals([1, 2], $collect->flatten(-1)->toArray());
    }

    public function testFlip(): void
    {
        $collect = $this->seq([1, 2]);
        self::assertEquals([1 => 0, 2 => 1], $collect->flip()->toArray());

        $collect = $this->seq(['a' => 'b', 'c' => 'd']);
        self::assertEquals(['b' => 'a', 'd' => 'c'], $collect->flip()->toArray());
    }

    public function testFold(): void
    {
        $reduced = $this->seq([])->fold(0, fn(int $i) => $i + 1);
        self::assertEquals(0, $reduced);

        $reduced = $this->seq(['a' => 1, 'b' => 2])->fold(collect(), fn(Collection $c, $i, $k) => $c->set($k, $i * 2));
        self::assertEquals(['a' => 2, 'b' => 4], $reduced->toArray());

        $reduced = $this->seq(['a' => 1, 'b' => 2])->fold((object)[], fn($c, $i, $k) => tap($c, static fn($c) => $c->$k = 0));
        self::assertEquals(['a' => 0, 'b' => 0], (array) $reduced);

        $reduced = $this->seq([1, 2, 3])->fold(0, fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function testGetIterator(): void
    {
        $iterator = $this->seq()->getIterator();
        self::assertEquals([], iterator_to_array($iterator));
    }

    public function testGroupBy(): void
    {
        $collect = $this->seq([1, 2, 3, 4, 5, 6]);
        self::assertEquals([[3, 6], [1, 4], [2, 5]], $collect->groupBy(fn($n) => $n % 3)->toArrayRecursive());

        $collect = $this->seq([
            ['id' => 1],
            ['id' => 1],
            ['id' => 2],
            ['dummy' => 3],
        ]);
        self::assertEquals([1 => [['id' => 1], ['id' => 1]], 2 => [['id' => 2]]], $collect->groupBy('id')->toArrayRecursive());
    }

    public function testIntersect(): void
    {
        $collect = $this->seq([1, 2, 3]);
        self::assertEquals([1], $collect->intersect([1])->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $collect->intersect([1])->toArray());

        $collect = $this->seq([]);
        self::assertEquals([], $collect->intersect([1])->toArray());
    }

    public function testIntersectKeys(): void
    {
        $collect = $this->seq([1, 2, 3]);
        self::assertEquals([1, 2], $collect->intersectKeys([1, 3])->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals([], $collect->intersectKeys([1])->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $collect->intersectKeys(['a' => 2])->toArray());

        $collect = $this->seq([]);
        self::assertEquals([], $collect->intersectKeys(['a' => 1])->toArray());

        $collect = $this->seq(['a' => 1]);
        self::assertEquals([], $collect->intersectKeys([])->toArray());
    }

    public function testIsAssoc(): void
    {
        $collect = $this->seq([]);
        self::assertTrue($collect->isAssoc());

        $collect = $this->seq([1, 2]);
        self::assertFalse($collect->isAssoc());

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->isAssoc());
    }

    public function testIsEmpty(): void
    {
        $collection = $this->seq([]);
        self::assertTrue($collection->isEmpty());

        $collection = $this->seq([1, 2]);
        self::assertFalse($collection->isEmpty());

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($collect->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        $collect = $this->seq([]);
        self::assertFalse($collect->isNotEmpty());

        $collect = $this->seq([1, 2]);
        self::assertTrue($collect->isNotEmpty());

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->isNotEmpty());
    }

    public function testIsList(): void
    {
        $collect = $this->seq([]);
        self::assertTrue($collect->isList());

        $collect = $this->seq([1, 2]);
        self::assertTrue($collect->isList());

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($collect->isList());
    }

    public function testJoin(): void
    {
        $collect = $this->seq([1, 2]);
        self::assertEquals('1, 2', $collect->join(', '));
        self::assertEquals('[1, 2', $collect->join(', ', '['));
        self::assertEquals('[1, 2]', $collect->join(', ', '[', ']'));

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals('1, 2', $collect->join(', '));
        self::assertEquals('[1, 2', $collect->join(', ', '['));
        self::assertEquals('[1, 2]', $collect->join(', ', '[', ']'));
    }

    public function testJsonSerialize(): void
    {
        $collect = $this->seq([]);
        self::assertEquals([], $collect->jsonSerialize());

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->jsonSerialize());
    }

    public function testKeyBy(): void
    {
        $collect = $this->seq([1, 2])->keyBy(fn($v) => 'a'.$v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $collect->toArray());

        $collect = $this->seq([['id' => 'b'], ['id' => 'c']])->keyBy(fn($v) => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $collect->toArray());
    }

    public function testKeyBy_WithDuplicateKey(): void
    {
        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b'], ['id' => 'b']])->keyBy(fn($v) => $v['id']);
    }

    public function testKeyBy_WithOverwrittenKey(): void
    {
        $collect = $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $collect->toArray());

        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], false);
    }

    public function testKeyBy_WithInvalidKey(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => false);
    }

    public function testKeys(): void
    {
        $keys = $this->seq([1,2])->keys();
        self::assertEquals([0,1], $keys->toArray());

        $keys = $this->seq(['a' => 1, 'b' => 2])->keys();
        self::assertEquals(['a', 'b'], $keys->toArray());
    }

    public function testLast(): void
    {
        $collect = $this->seq([10, 20]);
        self::assertEquals(20, $collect->last());
        self::assertEquals(20, $collect->last(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->last(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->last(fn() => false));
    }

    public function testLastIndex(): void
    {
        $collect = $this->seq([10, 20, 20]);
        self::assertEquals(1, $collect->lastIndex(fn($v, $k) => $k === 1));
        self::assertEquals(2, $collect->lastIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->lastIndex(fn() => false));
    }

    public function testLastKey(): void
    {
        $collect = $this->seq(['a' => 10, 'b' => 20, 'c' => 20]);
        self::assertEquals('c', $collect->lastKey());
        self::assertEquals('b', $collect->lastKey(fn($v, $k) => $k === 'b'));
        self::assertEquals('c', $collect->lastKey(fn($v, $k) => $v === 20));
        self::assertEquals(null, $collect->lastKey(fn() => false));
    }

    public function testLastOrFail(): void
    {
        $collect = $this->seq([10, 20]);
        self::assertEquals(20, $collect->lastOrFail());
        self::assertEquals(20, $collect->lastOrFail(fn($v, $k) => $k === 1));
        self::assertEquals(20, $collect->lastOrFail(fn($v, $k) => $v === 20));
    }

    public function testLastOrFail_Empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->lastOrFail();
    }

    public function testLastOrFail_BadCondition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->lastOrFail(fn(int $i) => $i > 2);
    }

    public function testMacro(): void
    {
        Sequence::macro('testMacro', fn($num) => $num * 100);
        $collect = $this->seq([1]);
        self::assertEquals(200, $collect->testMacro(2));
    }

    public function testMacroExists(): void
    {
        $name = 'testMacro2'.mt_rand();
        self::assertFalse(Sequence::macroExists($name));
        Sequence::macro($name, fn() => 1);
        self::assertTrue(Sequence::macroExists($name));
    }

    public function testMap(): void
    {
        $collect = $this->seq([1, 2, 3]);
        self::assertEquals([2, 4, 6], $collect->map(fn($i) => $i * 2)->toArray());
        self::assertEquals([0, 1, 2], $collect->map(fn($i, $k) => $k)->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 2, 'b' => 4, 'c' => 6], $collect->map(fn($i) => $i * 2)->toArray());
    }

    public function testMax(): void
    {
        $collect = $this->seq([1, 2, 3, 10, 1]);
        self::assertEquals(10, $collect->max());

        $collect = $this->seq([100, 2, 3, 10, 1]);
        self::assertEquals(100, $collect->max());

        $collect = $this->seq([1, 2, 3, 10, 1, -100, 90]);
        self::assertEquals(90, $collect->max());
    }

    public function testMaxBy(): void
    {
        self::assertEquals(
            null,
            $this->seq([])->maxBy(fn(array $arr) => 1),
            'maxBy with empty array'
        );

        self::assertEquals(
            2,
            $this->seq(['a' => 2, 'b' => 1])->maxBy(fn($v, $k) => $v),
            'maxBy using value'
        );

        self::assertEquals(1,
            $this->seq(['a' => 2, 'b' => 1])->maxBy(fn($v, $k) => $k),
            'maxBy using key'
        );
    }

    public function testMerge(): void
    {
        $empty = $this->seq([]);
        $merged = $empty->merge([1, [2]]);
        self::assertNotSame($empty, $merged);
        self::assertCount(0, $empty);
        self::assertEquals([1, [2]], $merged->toArray());

        $empty = $this->seq([]);
        $merged = $empty->merge([1, [2]]);
        self::assertEquals([1, [2]], $merged->toArray());

        $empty = $this->seq(['0' => 1]);
        $merged = $empty->merge([1, [2]]);
        self::assertEquals(['0' => 1, 1, [2]], $merged->toArray());

        $assoc = $this->seq([1, 'a' => [1, 2]]);
        $merged = $assoc->merge([1, 'a' => [3]]);
        self::assertSame([1, 'a' => [3], 1], $merged->toArray());

        $assoc = $this->seq([1, 'a' => [1, 2], 2]);
        $merged = $assoc->merge(['a' => [3], 3]);
        self::assertSame([1, 'a' => [3], 2, 3], $merged->toArray());
    }

    public function testMergeRecursive(): void
    {
        $collect = $this->seq([])->mergeRecursive([]);
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq([1, 2])->mergeRecursive([3]);
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = $this->seq(['a' => 1])->mergeRecursive(['a' => 2]);
        self::assertEquals(['a' => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1])->mergeRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 2, 'b' => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1])->mergeRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => ['c' => 1]], $collect->toArray());

        $collect = $this->seq(['a' => [1,2]])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $collect->toArray());

        $collect = $this->seq(['a' => ['b' => 1], 'd' => 4])->mergeRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $collect->toArray());
    }

    public function testMin(): void
    {
        $collect = $this->seq([1, 2, 3, 10, -1]);
        self::assertEquals(-1, $collect->min());

        $collect = $this->seq([0, -1]);
        self::assertEquals(-1, $collect->min());

        $collect = $this->seq([1, 10, -100]);
        self::assertEquals(-100, $collect->min());
    }

    public function testMinBy(): void
    {
        self::assertEquals(
            null,
            $this->seq([])->minBy(fn(array $arr) => 1),
            'minBy with empty array'
        );

        self::assertEquals(
            1,
            $this->seq(['a' => 2, 'b' => 1])->minBy(fn($v, $k) => $v),
            'minBy using value'
        );

        self::assertEquals(
            2,
            $this->seq(['a' => 2, 'b' => 1])->minBy(fn($v, $k) => $k),
            'minBy using key'
        );
    }

    public function testMinMax(): void
    {
        $collect = $this->seq([1]);
        self::assertEquals(['min' => 1, 'max' => 1], $collect->minMax());

        $collect = $this->seq([1, 10, -100]);
        self::assertEquals(['min' => -100, 'max' => 10], $collect->minMax());
    }

    public function testMinMax_Empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->minMax();
    }

    public function testNewInstance(): void
    {
        $collect = $this->seq([]);
        self::assertNotSame($collect, $collect->newInstance([]));
        self::assertEquals($collect, $collect->newInstance([]));

        $collect = $this->seq([1, 10]);
        self::assertEquals([], $collect->newInstance([])->toArray());
    }

    public function testNotContains(): void
    {
        self::assertTrue($this->seq([])->notContains(0));
        self::assertTrue($this->seq([])->notContains(null));
        self::assertTrue($this->seq([])->notContains([]));
        self::assertTrue($this->seq([null, 0])->notContains(false));
        self::assertTrue($this->seq([null, 0])->notContains(1));
        self::assertTrue($this->seq(['a' => 1])->notContains('a'));
        self::assertFalse($this->seq([null, 0])->notContains(null));
        self::assertFalse($this->seq([null, []])->notContains([]));
        self::assertFalse($this->seq(['a' => 1, 0])->notContains(1));
    }

    public function testNotContainsKey(): void
    {
        self::assertTrue($this->seq([])->notContainsKey(0));
        self::assertTrue($this->seq([])->notContainsKey(1));
        self::assertTrue($this->seq(['b' => 1])->notContainsKey('a'));
        self::assertFalse($this->seq([1])->notContainsKey(0));
        self::assertFalse($this->seq([11 => 1])->notContainsKey(11));
        self::assertFalse($this->seq(['a' => 1, 0])->notContainsKey('a'));
    }

    public function testNotEquals(): void
    {
        self::assertTrue($this->seq([])->notEquals($this->seq([1])));
        self::assertTrue($this->seq([])->notEquals($this->seq([null])));
        self::assertTrue($this->seq(['b' => 1])->notEquals($this->seq(['a' => 1])));
        self::assertFalse($this->seq([1])->notEquals($this->seq([1])));
        self::assertFalse($this->seq(['a' => 1])->notEquals($this->seq(['a' => 1])));
    }
    public function testOnly(): void
    {
        // with list array
        $collect = $this->seq([1, 2, 3]);
        self::assertEquals([1 => 2], $collect->only([1])->toArray());

        // with assoc array
        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
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
        self::assertEquals([], $this->seq([])->only(['a'])->toArray());
    }

    public function testPrioritize(): void
    {
        $collect = $this->seq([1, 2, 3])->prioritize(fn(int $i) => $i === 2);
        self::assertEquals([2, 1, 3], $collect->values()->toArray());

        $collect = $this->seq(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2])->prioritize(fn($_, string $k) => strlen($k) > 1);
        self::assertEquals(['bc', 'de', 'a', 'b'], $collect->keys()->toArray());

        $collect = $this->seq([1, 2, 3])->prioritize(fn() => false);
        self::assertEquals([1, 2, 3], $collect->toArray());
    }

    public function testReduce(): void
    {
        $reduced = $this->seq(['a' => 1])->reduce(fn(int $c, $i, $k) => 0);
        self::assertEquals(1, $reduced);

        $reduced = $this->seq(['a' => 1, 'b' => 2])->reduce(fn($val, $i) => $i * 2);
        self::assertEquals(4, $reduced);

        $reduced = $this->seq([1, 2, 3])->reduce(fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function testReduce_UnableToGuessInitial(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array to contain at least 1 elements. Got: 0');
        $this->seq([])->reduce(fn($c, $i, $k) => $k);
    }

    public function testRepeat(): void
    {
        $collect = $this->seq([1])->repeat(3);
        self::assertEquals([1, 1, 1], $collect->toArray(), 'Repeat single 3 times');

        $collect = $this->seq([1, 2])->repeat(2);
        self::assertEquals([1, 2, 1, 2], $collect->toArray(), 'Repeat multiple 3 times');

        $collect = $this->seq(['a' => 1, 'b' => 2])->repeat(2);
        self::assertEquals([1, 2, 1, 2], $collect->toArray(), 'Repeat hash 3 times (loses the keys)');

        $collect = $this->seq([1])->repeat(0);
        self::assertEquals([], $collect->toArray(), 'Repeat 0 times (does nothing)');
    }

    public function testRepeat_NegativeTimes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');

        $collect = $this->seq([1])->repeat(-1);
        self::assertEquals([], $collect->toArray(), 'Repeat -1 times (throws error)');
    }

    public function testReverse(): void
    {
        $collect = $this->seq([])->reverse();
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq([1, 2])->reverse();
        self::assertEquals([2, 1], $collect->toArray());

        $collect = $this->seq([100 => 1, 200 => 2])->reverse();
        self::assertEquals([200 => 2, 100 => 1], $collect->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 3])->reverse();
        self::assertEquals([3, 'b' => 2, 'a' => 1], $collect->toArray());

        $collect = $this->seq(['a' => 1, 2, 3, 4])->reverse();
        self::assertEquals([2 => 4, 1 => 3, 0 => 2, 'a' => 1], $collect->toArray());
    }

    public function testRotate(): void
    {
        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(1);
        self::assertEquals(['b' => 2, 'c' => 3, 'a' => 1], $collect->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(2);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(-1);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $collect->toArray());
    }

    public function testSample(): void
    {
        mt_srand(100);
        self::assertEquals(8, $this->seq(range(0, 10))->sample());
    }

    public function testSample_Empty(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('array_rand(): Argument #1 ($array) cannot be empty');
        $this->seq([])->sample();
    }

    public function testSampleMany(): void
    {
        mt_srand(100);
        self::assertEquals([8 => 8, 9 => 9], $this->seq(range(0, 10))->sampleMany(2)->toArray());
    }

    public function testSatisfyAll(): void
    {
        $collect = $this->seq([]);
        self::assertTrue($collect->satisfyAll(static fn($v) => is_int($v)));

        $collect = $this->seq([1, 2, 3]);
        self::assertTrue($collect->satisfyAll(static fn($v) => is_int($v)));

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertTrue($collect->satisfyAll(static fn($v, $k) => is_string($k)));

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 4]);
        self::assertFalse($collect->satisfyAll(static fn($k) => is_string($k)));
    }

    public function testSatisfyAny(): void
    {
        $empty = $this->seq([]);
        self::assertFalse($empty->satisfyAny(static fn() => true));

        $collect = $this->seq([1, null, 2, [3], false]);
        self::assertTrue($collect->satisfyAny(static fn($v) => true));
        self::assertFalse($collect->satisfyAny(static fn($v) => false));
        self::assertTrue($collect->satisfyAny(static fn($v) => is_array($v)));

        $collect = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($collect->satisfyAny(static fn($v, $k) => true));
        self::assertFalse($collect->satisfyAny(static fn($v) => false));
        self::assertTrue($collect->satisfyAny(static fn($v, $k) => $k === 'b'));
    }

    public function testShuffle(): void
    {
        mt_srand(100);
        self::assertEquals([1, 2, 4, 3, 2], $this->seq([1, 2, 2, 3, 4])->shuffle()->toArray());
        self::assertSame(['a' => 1, 'c' => 3, 'b' => 2, 'd' => 4], $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->shuffle()->toArray());
    }

    public function testSole(): void
    {
        self::assertEquals(1, $this->seq([1])->sole());
        self::assertEquals(1, $this->seq(['a' => 1])->sole());
        self::assertEquals(2, $this->seq([1, 2, 3])->sole(fn(int $i) => $i === 2));
    }

    public function testSole_ZeroItem(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 0 given.');
        $this->seq([])->sole();
    }

    public function testSole_MoreThanOneItem(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        $this->seq([1, 2])->sole();
    }

    public function testSort(): void
    {
        $collect = $this->seq([4, 2, 1, 3])->sort()->values();
        self::assertEquals([1, 2, 3, 4], $collect->toArray());

        $collect = $this->seq(['30', '2', '100'])->sort(SORT_NATURAL)->values();
        self::assertEquals(['2', '30', '100'], $collect->toArray());

        $collect = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sort();
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $collect->toArray());
    }

    public function testSortBy(): void
    {
        $collect = $this->seq([4, 2, 1, 3])->sortBy(fn($v) => $v)->values();
        self::assertEquals([1, 2, 3, 4], $collect->toArray());

        $collect = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $collect->toArray());
    }

    public function testSortByDesc(): void
    {
        $collect = $this->seq([4, 2, 1, 3])->sortByDesc(fn($v) => $v)->values();
        self::assertEquals([4, 3, 2, 1], $collect->toArray());

        $collect = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $collect->toArray());
    }

    public function testSortDesc(): void
    {
        $collect = $this->seq([4, 2, 1, 3])->sortDesc()->values();
        self::assertEquals([4, 3, 2, 1], $collect->toArray());

        $collect = $this->seq(['30', '100', '2'])->sortDesc(SORT_NATURAL)->values();
        self::assertEquals(['100', '30', '2'], $collect->toArray());

        $collect = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sortDesc();
        self::assertEquals(['a' => 3, 'c' => 2, 'b' => 1], $collect->toArray());
    }

    public function testSortKeys(): void
    {
        $collect = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKey();
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $collect->toArray());

        $collect = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKey(SORT_NATURAL);
        self::assertEquals(['2' => 0, '30' => 2, '100' => 1], $collect->toArray());
    }

    public function testSortKeysDesc(): void
    {
        $collect = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKeyDesc();
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $collect->toArray());

        $collect = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKeyDesc(SORT_NATURAL);
        self::assertEquals(['100' => 1, '30' => 2, '2' => 0], $collect->toArray());
    }

    public function testSortWith(): void
    {
        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->sortWith(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $collect->toArray());
    }

    public function testSortWithKey(): void
    {
        $collect = $this->seq([1 => 'a', 3 => 'b', 2 => 'c'])->sortWithKey(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals([1 => 'a', 2 => 'c', 3 => 'b'], $collect->toArray());
    }

    public function testSum(): void
    {
        $sum = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->sum();
        self::assertEquals(6, $sum);

        $sum = $this->seq([1, 1, 1])->sum();
        self::assertEquals(3, $sum);

        $sum = $this->seq([0.1, 0.2])->sum();
        self::assertEquals(0.3, $sum);

        $sum = $this->seq([])->sum();
        self::assertEquals(0, $sum);
    }

    public function testSum_ThrowOnSumOfString(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unsupported operand types: int + string');
        $this->seq(['a', 'b'])->sum();
    }

    public function testTake(): void
    {
        $collect = $this->seq([2, 3, 4])->take(2);
        self::assertEquals([2, 3], $collect->toArray());

        $collect = $this->seq([2, 3, 4])->take(-1);
        self::assertEquals([4], $collect->toArray());

        $collect = $this->seq([2, 3, 4])->take(0);
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->take(1);
        self::assertEquals(['b' => 1], $collect->toArray());

    }

    public function testTakeUntil(): void
    {
        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => $v > 2);
        self::assertEquals(['b' => 1], $collect->toArray());

        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => false);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $collect->toArray());

        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => true);
        self::assertEquals([], $collect->toArray());
    }

    public function testTakeWhile(): void
    {
        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 4])->takeWhile(fn($v) => $v < 4);
        self::assertEquals(['b' => 1, 'a' => 3], $collect->toArray());

        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => false);
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => true);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $collect->toArray());
    }

    public function testTally(): void
    {
        $collect = $this->seq([1, 1, 1, 2, 3, 3])->tally();
        self::assertEquals([1 => 3, 2 => 1, 3 => 2], $collect->toArray());

        $collect = $this->seq(['b' => 1, 'a' => 1, 'c' => 1])->tally();
        self::assertEquals([1 => 3], $collect->toArray());
    }

    public function testTap(): void
    {
        $collect = $this->seq([1, 2])->tap(fn() => 100);
        self::assertEquals([1, 2], $collect->toArray());

        $cnt = 0;
        $collect = $this->seq([])->tap(function() use (&$cnt) { $cnt+= 1; });
        self::assertEquals([], $collect->toArray());
        self::assertEquals(1, $cnt);
    }

    public function testToArray(): void
    {
        self::assertEquals([], $this->seq([])->toArray());
        self::assertEquals([1, 2], $this->seq([1, 2])->toArray());
        self::assertEquals(['a' => 1], $this->seq(['a' => 1])->toArray());

        $inner = $this->seq([1, 2]);
        self::assertEquals(['a' => $inner], $this->seq(['a' => $inner])->toArray());
    }

    public function testToArrayRecursive(): void
    {
        // no depth defined
        $inner = $this->seq([1, 2]);
        $array = $this->seq(['a' => $inner])->toArrayRecursive();
        self::assertEquals(['a' => [1, 2]], $array);

        // test each depth
        $inner1 = $this->seq([1]);
        $inner2 = $this->seq([2, 3, $inner1]);
        $collect = $this->seq(['a' => $inner2]);
        self::assertEquals(['a' => $inner2], $collect->toArrayRecursive(1));
        self::assertEquals(['a' => [2, 3, $inner1]], $collect->toArrayRecursive(2));
        self::assertEquals(['a' => [2, 3, [1]]], $collect->toArrayRecursive(3));
    }

    public function testToJson(): void
    {
        $json = $this->seq([1, 2])->toJson();
        self::assertEquals("[1,2]", $json);

        $json = $this->seq(['a' => 1, 'b' => 2])->toJson();
        self::assertEquals("{\"a\":1,\"b\":2}", $json);

        $json = $this->seq(["あ"])->toJson();
        self::assertEquals("[\"あ\"]", $json);

        $json = $this->seq([1])->toJson(JSON_PRETTY_PRINT);
        self::assertEquals("[\n    1\n]", $json);
    }

    public function testToUrlQuery(): void
    {
        $query = $this->seq(['a' => 1])->toUrlQuery('t');
        self::assertEquals(urlencode('t[a]').'=1', $query);

        $query = $this->seq(['a' => 1, 'b' => 2])->toUrlQuery();
        self::assertEquals("a=1&b=2", $query);
    }

    public function testUnion(): void
    {
        $collect = $this->seq([])->union([]);
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq(['a' => 1])->union(['a' => 2]);
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->seq(['a' => ['b' => 1]])->union(['a' => ['c' => 2]]);
        self::assertEquals(['a' => ['b' => 1]], $collect->toArray());
    }

    public function testUnionRecursive(): void
    {
        $collect = $this->seq([])->unionRecursive([]);
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq([1, 2])->unionRecursive([3]);
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = $this->seq(['a' => 1])->unionRecursive(['a' => 2]);
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->seq(['a' => 1])->unionRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1])->unionRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => 1], $collect->toArray());

        $collect = $this->seq(['a' => [1,2]])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $collect->toArray());

        $collect = $this->seq(['a' => ['b' => 1], 'd' => 4])->unionRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $collect->toArray());
    }

    public function testUnique(): void
    {
        $collect = $this->seq([])->unique();
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq([1, 1, 2, 2])->unique();
        self::assertEquals([0 => 1, 2 => 2], $collect->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 2])->unique();
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $collect = $this->seq([])->merge($values)->merge($values)->unique();
        self::assertEquals($values, $collect->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $collect = $this->seq($values)->repeat(2)->unique();
        self::assertEquals($values, $collect->toArray());
    }

    public function testUniqueBy(): void
    {
        $collect = $this->seq([])->uniqueBy(static fn() => 1);
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq([1,2,3,4])->uniqueBy(static fn($v) => $v % 2);
        self::assertEquals([1, 2], $collect->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2, 'c' => 2])->uniqueBy(static fn($v) => $v % 2);
        self::assertEquals(['a' => 1, 'b' => 2], $collect->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $collect = $this->seq($values)->repeat(2)->uniqueBy(static fn($v) => $v);
        self::assertEquals($values, $collect->toArray());
    }

    public function testValues(): void
    {
        $collect = $this->seq([])->values();
        self::assertEquals([], $collect->toArray());

        $collect = $this->seq([1, 1, 2])->values()->reverse();
        self::assertEquals([2, 1, 1], $collect->toArray());

        $collect = $this->seq(['a' => 1, 'b' => 2])->values();
        self::assertEquals([1, 2], $collect->toArray());
    }
}
