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
        self::assertEquals([1, 2], $chunked->firstOrNull()->toArray());
        self::assertEquals([3], $chunked->lastOrNull()->toArray());

        // size larger than items -> returns everything
        $chunked = $seq->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals([1, 2, 3], $chunked->firstOrNull()->toArray());
        self::assertNotSame($chunked, $seq);

        $assoc = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);

        // test preserveKeys: true
        $chunked = $assoc->chunk(2);
        self::assertCount(2, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2], $chunked->firstOrNull()->toArray());
        self::assertEquals(['c' => 3], $chunked->lastOrNull()->toArray());

        // size larger than items -> returns everything
        $chunked = $assoc->chunk(4);
        self::assertCount(1, $chunked);
        self::assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $chunked->firstOrNull()->toArray());
        self::assertNotSame($chunked, $assoc);
    }

    public function testChunkInvalidSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $this->seq([1])->chunk(0);
    }

    public function testCoalesce_Empty(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be not null. null given.');
        $this->seq([])->coalesce();
    }

    public function testCoalesce_OnlyNull(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be not null. null given.');
        $this->seq([null])->coalesce();
    }

    public function testCoalesceOrNull(): void
    {
        $result = $this->seq()->coalesceOrNull();
        self::assertNull($result);

        $result = $this->seq([null, 0, 1])->coalesceOrNull();
        self::assertEquals(0, $result);

        $result = $this->seq([0, null, 1])->coalesceOrNull();
        self::assertEquals(0, $result);

        $result = $this->seq(['', null, 1])->coalesceOrNull();
        self::assertEquals('', $result);

        $result = $this->seq(['', null, 1])->coalesceOrNull();
        self::assertEquals('', $result);

        $result = $this->seq([[], null, 1])->coalesceOrNull();
        self::assertEquals([], $result);

        $result = $this->seq([null, [], 1])->coalesceOrNull();
        self::assertEquals([], $result);

        $result = $this->seq([null, null, 1])->coalesceOrNull();
        self::assertEquals(1, $result);
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
        $seq = $this->seq([1, null, 2, [3], false]);
        self::assertTrue($seq->contains(1));
        self::assertTrue($seq->contains(null));
        self::assertTrue($seq->contains([3]));
        self::assertTrue($seq->contains(false));
        self::assertFalse($seq->contains(3));
        self::assertFalse($seq->contains([]));

        // assoc: compared with value
        $seq = $this->seq(['a' => 1]);
        self::assertTrue($seq->contains(1));
        self::assertFalse($seq->contains(['a' => 1]));
        self::assertFalse($seq->contains(['a']));
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
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['b' => 2, 'c' => 3], $seq->drop(1)->toArray());

        // over value
        $seq = $this->seq(['a' => 1]);
        self::assertEquals([], $seq->drop(2)->toArray());

        // negative
        $seq = $this->seq(['a' => 1, 'b' => 1]);
        self::assertEquals(['a' => 1], $seq->drop(-1)->toArray());

        // zero
        $seq = $this->seq(['a' => 1]);
        self::assertEquals(['a' => 1], $seq->drop(0)->toArray());
    }

    public function testDropUntil(): void
    {
        // look at value
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $seq->dropUntil(fn($v) => $v >= 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $seq->dropUntil(fn($v, $k) => $k === 'c')->toArray());

        // drop until null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Kirameki\Support\Arr::verify(): Return value must be of type bool, null returned');
        $seq->dropUntil(fn($v, $k) => null)->toArray();
    }

    public function testDropWhile(): void
    {
        // look at value
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['c' => 3], $seq->dropWhile(fn($v) => $v < 3)->toArray());

        // look at key
        self::assertEquals(['c' => 3], $seq->dropWhile(fn($v, $k) => $k !== 'c')->toArray());

        // drop until null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Kirameki\Support\Arr::verify(): Return value must be of type bool, null returned');
        $seq->dropWhile(fn($v, $k) => null)->toArray();
    }

    public function testEach(): void
    {
        $seq = $this->seq(['a' => 1, 'b' => 2]);
        $seq->each(function ($v, $k) {
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
        $seq = $this->seq(['a' => 1, 'b' => 2]);
        $seq->eachWithIndex(function ($v, $k, $n) {
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
        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $seq->except(['a'])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $seq->except(['a', 'c'])->toArray());
    }

    public function testFilter(): void
    {
        // sequence: remove ones with empty value
        $seq = $this->seq([0, 1, '', '0', null]);
        self::assertEquals([1 => 1], $seq->filter(fn($item) => !empty($item))->toArray());

        // assoc: removes null / false / 0 / empty string / empty array
        $seq = $this->seq(['a' => null, 'b' => false, 'c' => 0, 'd' => '', 'e' => '0', 'f' => []]);
        self::assertEquals([], $seq->filter(fn($item) => !empty($item))->toArray());

        // assoc: removes ones with condition
        self::assertEquals(['d' => ''], $seq->filter(fn($v) => $v === '')->toArray());
    }

    public function testFirst(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(10, $seq->firstOrNull());
        self::assertEquals(20, $seq->firstOrNull(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->firstOrNull(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->firstOrNull(fn() => false));
    }

    public function testFirstIndex(): void
    {
        $seq = $this->seq([10, 20, 20, 30]);
        self::assertEquals(2, $seq->firstIndex(fn($v, $k) => $k === 2));
        self::assertEquals(1, $seq->firstIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->firstIndex(fn() => false));
    }

    public function testFirstKey(): void
    {
        $seq = $this->seq([10, 20, 30]);
        self::assertEquals(1, $seq->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals(2, $seq->firstKey(fn($v, $k) => $k === 2));

        $seq = $this->seq(['a' => 10, 'b' => 20, 'c' => 30]);
        self::assertEquals('b', $seq->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals('c', $seq->firstKey(fn($v, $k) => $k === 'c'));
    }

    public function testFirstOrFail(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(10, $seq->first());
        self::assertEquals(20, $seq->first(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->first(fn($v, $k) => $v === 20));
    }

    public function testFirstOrFail_Empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->first();
    }

    public function testFirstOrFail_BadCondition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->first(fn(int $i) => $i > 2);
    }

    public function testFlatMap(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, -1, 2, -2], $seq->flatMap(fn($i) => [$i, -$i])->toArray());

        $seq = $this->seq([['a'], ['b']]);
        self::assertEquals(['a', 'b'], $seq->flatMap(fn($a) => $a)->toArray());

        $seq = $this->seq([['a' => 1], [2], 2]);
        self::assertEquals([1, 2, 2], $seq->flatMap(fn($a) => $a)->toArray());
    }

    public function testFlatten(): void
    {
        // nothing to flatten
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten()->toArray());

        // flatten only 1 as default
        $seq = $this->seq([[1, [2, 2]], 3]);
        self::assertEquals([1, [2, 2], 3], $seq->flatten()->toArray());

        // flatten more than 1
        $seq = $this->seq([['a' => 1], [1, [2, [3, 3], 2], 1]]);
        self::assertEquals([1, 1, 2, [3, 3], 2, 1], $seq->flatten(2)->toArray());

        // assoc info is lost
        $seq = $this->seq([['a'], 'b', ['c' => 'd']]);
        self::assertEquals(['a', 'b', 'd'], $seq->flatten()->toArray());
    }

    public function testFlatten_ZeroDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten(0)->toArray());
    }

    public function testFlatten_NegativeDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: -1');
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten(-1)->toArray());
    }

    public function testFlip(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals([1 => 0, 2 => 1], $seq->flip()->toArray());

        $seq = $this->seq(['a' => 'b', 'c' => 'd']);
        self::assertEquals(['b' => 'a', 'd' => 'c'], $seq->flip()->toArray());
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
        $seq = $this->seq([1, 2, 3, 4, 5, 6]);
        self::assertEquals([[3, 6], [1, 4], [2, 5]], $seq->groupBy(fn($n) => $n % 3)->toArrayRecursive());

        $seq = $this->seq([
            ['id' => 1],
            ['id' => 1],
            ['id' => 2],
            ['dummy' => 3],
        ]);
        self::assertEquals([1 => [['id' => 1], ['id' => 1]], 2 => [['id' => 2]]], $seq->groupBy('id')->toArrayRecursive());
    }

    public function testIntersect(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([1], $seq->intersect([1])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $seq->intersect([1])->toArray());

        $seq = $this->seq([]);
        self::assertEquals([], $seq->intersect([1])->toArray());
    }

    public function testIntersectKeys(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([1, 2], $seq->intersectKeys([1, 3])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals([], $seq->intersectKeys([1])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $seq->intersectKeys(['a' => 2])->toArray());

        $seq = $this->seq([]);
        self::assertEquals([], $seq->intersectKeys(['a' => 1])->toArray());

        $seq = $this->seq(['a' => 1]);
        self::assertEquals([], $seq->intersectKeys([])->toArray());
    }

    public function testIsAssoc(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isAssoc());

        $seq = $this->seq([1, 2]);
        self::assertFalse($seq->isAssoc());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->isAssoc());
    }

    public function testIsEmpty(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isEmpty());

        $seq = $this->seq([1, 2]);
        self::assertFalse($seq->isEmpty());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($seq->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        $seq = $this->seq([]);
        self::assertFalse($seq->isNotEmpty());

        $seq = $this->seq([1, 2]);
        self::assertTrue($seq->isNotEmpty());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->isNotEmpty());
    }

    public function testIsList(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isList());

        $seq = $this->seq([1, 2]);
        self::assertTrue($seq->isList());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($seq->isList());
    }

    public function testJoin(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals('1, 2', $seq->join(', '));
        self::assertEquals('[1, 2', $seq->join(', ', '['));
        self::assertEquals('[1, 2]', $seq->join(', ', '[', ']'));

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals('1, 2', $seq->join(', '));
        self::assertEquals('[1, 2', $seq->join(', ', '['));
        self::assertEquals('[1, 2]', $seq->join(', ', '[', ']'));
    }

    public function testJsonSerialize(): void
    {
        $seq = $this->seq([]);
        self::assertEquals([], $seq->jsonSerialize());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->jsonSerialize());
    }

    public function testKeyBy(): void
    {
        $seq = $this->seq([1, 2])->keyBy(fn($v) => 'a'.$v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $seq->toArray());

        $seq = $this->seq([['id' => 'b'], ['id' => 'c']])->keyBy(fn($v) => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $seq->toArray());
    }

    public function testKeyBy_WithDuplicateKey(): void
    {
        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b'], ['id' => 'b']])->keyBy(fn($v) => $v['id']);
    }

    public function testKeyBy_WithOverwrittenKey(): void
    {
        $seq = $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $seq->toArray());

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
        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->last());
        self::assertEquals(20, $seq->last(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->last(fn($v, $k) => $v === 20));
    }

    public function testLastIndex(): void
    {
        $seq = $this->seq([10, 20, 20]);
        self::assertEquals(1, $seq->lastIndex(fn($v, $k) => $k === 1));
        self::assertEquals(2, $seq->lastIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastIndex(fn() => false));
    }

    public function testLastKey(): void
    {
        $seq = $this->seq(['a' => 10, 'b' => 20, 'c' => 20]);
        self::assertEquals('c', $seq->lastKey());
        self::assertEquals('b', $seq->lastKey(fn($v, $k) => $k === 'b'));
        self::assertEquals('c', $seq->lastKey(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastKey(fn() => false));
    }

    public function testLastOrNull(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->lastOrNull());
        self::assertEquals(20, $seq->lastOrNull(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->lastOrNull(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastOrNull(fn() => false));
    }

    public function testLastOrFail_Empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->last();
    }

    public function testLastOrFail_BadCondition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->last(fn(int $i) => $i > 2);
    }

    public function testMacro(): void
    {
        Sequence::macro('testMacro', fn($num) => $num * 100);
        $seq = $this->seq([1]);
        self::assertEquals(200, $seq->testMacro(2));
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
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([2, 4, 6], $seq->map(fn($i) => $i * 2)->toArray());
        self::assertEquals([0, 1, 2], $seq->map(fn($i, $k) => $k)->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 2, 'b' => 4, 'c' => 6], $seq->map(fn($i) => $i * 2)->toArray());
    }

    public function testMax(): void
    {
        $seq = $this->seq([1, 2, 3, 10, 1]);
        self::assertEquals(10, $seq->max());

        $seq = $this->seq([100, 2, 3, 10, 1]);
        self::assertEquals(100, $seq->max());

        $seq = $this->seq([1, 2, 3, 10, 1, -100, 90]);
        self::assertEquals(90, $seq->max());
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
        $seq = $this->seq([])->mergeRecursive([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 2])->mergeRecursive([3]);
        self::assertEquals([1, 2, 3], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['a' => 2]);
        self::assertEquals(['a' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 2, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => ['c' => 1]], $seq->toArray());

        $seq = $this->seq(['a' => [1,2]])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1], 'd' => 4])->mergeRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $seq->toArray());
    }

    public function testMin(): void
    {
        $seq = $this->seq([1, 2, 3, 10, -1]);
        self::assertEquals(-1, $seq->min());

        $seq = $this->seq([0, -1]);
        self::assertEquals(-1, $seq->min());

        $seq = $this->seq([1, 10, -100]);
        self::assertEquals(-100, $seq->min());
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
        $seq = $this->seq([1]);
        self::assertEquals(['min' => 1, 'max' => 1], $seq->minMax());

        $seq = $this->seq([1, 10, -100]);
        self::assertEquals(['min' => -100, 'max' => 10], $seq->minMax());
    }

    public function testMinMax_Empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->minMax();
    }

    public function testNewInstance(): void
    {
        $seq = $this->seq([]);
        self::assertNotSame($seq, $seq->newInstance([]));
        self::assertEquals($seq, $seq->newInstance([]));

        $seq = $this->seq([1, 10]);
        self::assertEquals([], $seq->newInstance([])->toArray());
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
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([1 => 2], $seq->only([1])->toArray());

        // with assoc array
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->only(['a', 'b'])->toArray());

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], $seq->only(['c', 'b'])->toArray());

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], $seq->only(['x' => 'c', 'b'])->toArray());
    }

    public function testOnly_WithUndefinedKey(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "a"');
        self::assertEquals([], $this->seq([])->only(['a'])->toArray());
    }

    public function testPrioritize(): void
    {
        $seq = $this->seq([1, 2, 3])->prioritize(fn(int $i) => $i === 2);
        self::assertEquals([2, 1, 3], $seq->values()->toArray());

        $seq = $this->seq(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2])->prioritize(fn($_, string $k) => strlen($k) > 1);
        self::assertEquals(['bc', 'de', 'a', 'b'], $seq->keys()->toArray());

        $seq = $this->seq([1, 2, 3])->prioritize(fn() => false);
        self::assertEquals([1, 2, 3], $seq->toArray());
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
        $seq = $this->seq([1])->repeat(3);
        self::assertEquals([1, 1, 1], $seq->toArray(), 'Repeat single 3 times');

        $seq = $this->seq([1, 2])->repeat(2);
        self::assertEquals([1, 2, 1, 2], $seq->toArray(), 'Repeat multiple 3 times');

        $seq = $this->seq(['a' => 1, 'b' => 2])->repeat(2);
        self::assertEquals([1, 2, 1, 2], $seq->toArray(), 'Repeat hash 3 times (loses the keys)');

        $seq = $this->seq([1])->repeat(0);
        self::assertEquals([], $seq->toArray(), 'Repeat 0 times (does nothing)');
    }

    public function testRepeat_NegativeTimes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');

        $seq = $this->seq([1])->repeat(-1);
        self::assertEquals([], $seq->toArray(), 'Repeat -1 times (throws error)');
    }

    public function testReverse(): void
    {
        $seq = $this->seq([])->reverse();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 2])->reverse();
        self::assertEquals([2, 1], $seq->toArray());

        $seq = $this->seq([100 => 1, 200 => 2])->reverse();
        self::assertEquals([200 => 2, 100 => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 3])->reverse();
        self::assertEquals([3, 'b' => 2, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 2, 3, 4])->reverse();
        self::assertEquals([2 => 4, 1 => 3, 0 => 2, 'a' => 1], $seq->toArray());
    }

    public function testRotate(): void
    {
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(1);
        self::assertEquals(['b' => 2, 'c' => 3, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(2);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(-1);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $seq->toArray());
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
        $seq = $this->seq([]);
        self::assertTrue($seq->satisfyAll(static fn($v) => is_int($v)));

        $seq = $this->seq([1, 2, 3]);
        self::assertTrue($seq->satisfyAll(static fn($v) => is_int($v)));

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertTrue($seq->satisfyAll(static fn($v, $k) => is_string($k)));

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 4]);
        self::assertFalse($seq->satisfyAll(static fn($k) => is_string($k)));
    }

    public function testSatisfyAny(): void
    {
        $empty = $this->seq([]);
        self::assertFalse($empty->satisfyAny(static fn() => true));

        $seq = $this->seq([1, null, 2, [3], false]);
        self::assertTrue($seq->satisfyAny(static fn($v) => true));
        self::assertFalse($seq->satisfyAny(static fn($v) => false));
        self::assertTrue($seq->satisfyAny(static fn($v) => is_array($v)));

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->satisfyAny(static fn($v, $k) => true));
        self::assertFalse($seq->satisfyAny(static fn($v) => false));
        self::assertTrue($seq->satisfyAny(static fn($v, $k) => $k === 'b'));
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
        $seq = $this->seq([4, 2, 1, 3])->sort()->values();
        self::assertEquals([1, 2, 3, 4], $seq->toArray());

        $seq = $this->seq(['30', '2', '100'])->sort(SORT_NATURAL)->values();
        self::assertEquals(['2', '30', '100'], $seq->toArray());

        $seq = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sort();
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $seq->toArray());
    }

    public function testSortBy(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortBy(fn($v) => $v)->values();
        self::assertEquals([1, 2, 3, 4], $seq->toArray());

        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $seq->toArray());
    }

    public function testSortByDesc(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortByDesc(fn($v) => $v)->values();
        self::assertEquals([4, 3, 2, 1], $seq->toArray());

        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $seq->toArray());
    }

    public function testSortDesc(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortDesc()->values();
        self::assertEquals([4, 3, 2, 1], $seq->toArray());

        $seq = $this->seq(['30', '100', '2'])->sortDesc(SORT_NATURAL)->values();
        self::assertEquals(['100', '30', '2'], $seq->toArray());

        $seq = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sortDesc();
        self::assertEquals(['a' => 3, 'c' => 2, 'b' => 1], $seq->toArray());
    }

    public function testSortKeys(): void
    {
        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKey();
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $seq->toArray());

        $seq = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKey(SORT_NATURAL);
        self::assertEquals(['2' => 0, '30' => 2, '100' => 1], $seq->toArray());
    }

    public function testSortKeysDesc(): void
    {
        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKeyDesc();
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKeyDesc(SORT_NATURAL);
        self::assertEquals(['100' => 1, '30' => 2, '2' => 0], $seq->toArray());
    }

    public function testSortWith(): void
    {
        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->sortWith(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $seq->toArray());
    }

    public function testSortWithKey(): void
    {
        $seq = $this->seq([1 => 'a', 3 => 'b', 2 => 'c'])->sortWithKey(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals([1 => 'a', 2 => 'c', 3 => 'b'], $seq->toArray());
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
        $seq = $this->seq([2, 3, 4])->take(2);
        self::assertEquals([2, 3], $seq->toArray());

        $seq = $this->seq([2, 3, 4])->take(-1);
        self::assertEquals([4], $seq->toArray());

        $seq = $this->seq([2, 3, 4])->take(0);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->take(1);
        self::assertEquals(['b' => 1], $seq->toArray());

    }

    public function testTakeUntil(): void
    {
        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => $v > 2);
        self::assertEquals(['b' => 1], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => false);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => true);
        self::assertEquals([], $seq->toArray());
    }

    public function testTakeWhile(): void
    {
        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 4])->takeWhile(fn($v) => $v < 4);
        self::assertEquals(['b' => 1, 'a' => 3], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => false);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => true);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $seq->toArray());
    }

    public function testTally(): void
    {
        $seq = $this->seq([1, 1, 1, 2, 3, 3])->tally();
        self::assertEquals([1 => 3, 2 => 1, 3 => 2], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 1, 'c' => 1])->tally();
        self::assertEquals([1 => 3], $seq->toArray());
    }

    public function testTap(): void
    {
        $seq = $this->seq([1, 2])->tap(fn() => 100);
        self::assertEquals([1, 2], $seq->toArray());

        $cnt = 0;
        $seq = $this->seq([])->tap(function() use (&$cnt) { $cnt+= 1; });
        self::assertEquals([], $seq->toArray());
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
        $seq = $this->seq(['a' => $inner2]);
        self::assertEquals(['a' => $inner2], $seq->toArrayRecursive(1));
        self::assertEquals(['a' => [2, 3, $inner1]], $seq->toArrayRecursive(2));
        self::assertEquals(['a' => [2, 3, [1]]], $seq->toArrayRecursive(3));
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
        $seq = $this->seq([])->union([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['a' => 1])->union(['a' => 2]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1]])->union(['a' => ['c' => 2]]);
        self::assertEquals(['a' => ['b' => 1]], $seq->toArray());
    }

    public function testUnionRecursive(): void
    {
        $seq = $this->seq([])->unionRecursive([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 2])->unionRecursive([3]);
        self::assertEquals([1, 2, 3], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['a' => 2]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => [1,2]])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1], 'd' => 4])->unionRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $seq->toArray());
    }

    public function testUnique(): void
    {
        $seq = $this->seq([])->unique();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 1, 2, 2])->unique();
        self::assertEquals([0 => 1, 2 => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 2])->unique();
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $seq = $this->seq([])->merge($values)->merge($values)->unique();
        self::assertEquals($values, $seq->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $seq = $this->seq($values)->repeat(2)->unique();
        self::assertEquals($values, $seq->toArray());
    }

    public function testUniqueBy(): void
    {
        $seq = $this->seq([])->uniqueBy(static fn() => 1);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1,2,3,4])->uniqueBy(static fn($v) => $v % 2);
        self::assertEquals([1, 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 2])->uniqueBy(static fn($v) => $v % 2);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $seq = $this->seq($values)->repeat(2)->uniqueBy(static fn($v) => $v);
        self::assertEquals($values, $seq->toArray());
    }

    public function testValues(): void
    {
        $seq = $this->seq([])->values();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 1, 2])->values()->reverse();
        self::assertEquals([2, 1, 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2])->values();
        self::assertEquals([1, 2], $seq->toArray());
    }
}
