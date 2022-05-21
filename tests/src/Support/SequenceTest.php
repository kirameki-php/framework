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

    public function test___construct(): void
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

    public function test___construct_bad_argument(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument #1 ($items) must be of type ?iterable, int given');
        new Sequence(1);
    }

    public function test_at(): void
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

    public function test_average(): void
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

    public function test_average_not_empty(): void
    {
        $this->expectException(DivisionByZeroError::class);
        $this->seq([])->average(allowEmpty: false);
    }

    public function test_chunk(): void
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

    public function test_chunk_invalid_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $this->seq([1])->chunk(0);
    }

    public function test_coalesce_empty(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be not null. null given.');
        $this->seq([])->coalesce();
    }

    public function test_coalesce_only_null(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Expected value to be not null. null given.');
        $this->seq([null])->coalesce();
    }

    public function test_coalesceOrNull(): void
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

    public function test_compact(): void
    {
        // empty but not same instance
        $empty = $this->seq();
        self::assertNotSame($empty, $empty->compact());

        // list: removes nulls
        $compacted = $this->seq([1, null, null, 2])->compact();
        self::assertCount(2, $compacted);
        self::assertEquals([1, 2], $compacted->toArray());

        // list: no nulls
        $seq = $this->seq([1, 2]);
        $compacted = $seq->compact();
        self::assertNotSame($seq, $compacted);
        self::assertCount(2, $compacted);
        self::assertEquals([1, 2], $compacted->toArray());

        // list: all nulls
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

    public function test_contains(): void
    {
        $empty = $this->seq();
        self::assertFalse($empty->contains(null));

        // list: compared with value
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

    public function test_containsKey(): void
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

    public function test_copy(): void
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

    public function test_count(): void
    {
        // empty
        $empty = $this->seq();
        self::assertEquals(0, $empty->count());

        // count default
        $simple = $this->seq([1, 2, 3]);
        self::assertEquals(3, $simple->count());
    }

    public function test_countBy(): void
    {
        $simple = $this->seq([1, 2, 3]);
        self::assertEquals(2, $simple->countBy(fn($v) => $v > 1));
    }

    public function test_diff(): void
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

    public function test_diffKeys(): void
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

    public function test_drop(): void
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

    public function test_dropUntil(): void
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

    public function test_dropWhile(): void
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

    public function test_each(): void
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

    public function test_except(): void
    {
        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $seq->except(['a'])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['b' => 2], $seq->except(['a', 'c'])->toArray());
    }

    public function test_filter(): void
    {
        // list: remove ones with empty value
        $seq = $this->seq([0, 1, '', '0', null]);
        self::assertEquals([1], $seq->filter(fn($item) => !empty($item))->toArray());

        // assoc: removes null / false / 0 / empty string / empty array
        $seq = $this->seq(['a' => null, 'b' => false, 'c' => 0, 'd' => '', 'e' => '0', 'f' => []]);
        self::assertEquals([], $seq->filter(fn($item) => !empty($item))->toArray());

        // assoc: removes ones with condition
        self::assertEquals(['d' => ''], $seq->filter(fn($v) => $v === '')->toArray());
    }

    public function test_first(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(10, $seq->first());
        self::assertEquals(20, $seq->first(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->first(fn($v, $k) => $v === 20));
    }

    public function test_first_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->first();
    }

    public function test_first_bad_condition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->first(fn(int $i) => $i > 2);
    }

    public function test_firstIndex(): void
    {
        $seq = $this->seq([10, 20, 20, 30]);
        self::assertEquals(2, $seq->firstIndex(fn($v, $k) => $k === 2));
        self::assertEquals(1, $seq->firstIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->firstIndex(fn() => false));
    }

    public function test_firstKey(): void
    {
        $seq = $this->seq([10, 20, 30]);
        self::assertEquals(1, $seq->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals(2, $seq->firstKey(fn($v, $k) => $k === 2));

        $seq = $this->seq(['a' => 10, 'b' => 20, 'c' => 30]);
        self::assertEquals('b', $seq->firstKey(fn($v, $k) => $v === 20));
        self::assertEquals('c', $seq->firstKey(fn($v, $k) => $k === 'c'));
    }

    public function test_firstOrNull(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(10, $seq->firstOrNull());
        self::assertEquals(20, $seq->firstOrNull(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->firstOrNull(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->firstOrNull(fn() => false));
    }

    public function test_flatMap(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, -1, 2, -2], $seq->flatMap(fn($i) => [$i, -$i])->toArray());

        $seq = $this->seq([['a'], ['b']]);
        self::assertEquals(['a', 'b'], $seq->flatMap(fn($a) => $a)->toArray());

        $seq = $this->seq([['a' => 1], [2], 2]);
        self::assertEquals([1, 2, 2], $seq->flatMap(fn($a) => $a)->toArray());
    }

    public function test_flatten(): void
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

    public function test_flatten_zero_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten(0)->toArray());
    }

    public function test_flatten_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: -1');
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten(-1)->toArray());
    }

    public function test_flip(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals([1 => 0, 2 => 1], $seq->flip()->toArray());

        $seq = $this->seq(['a' => 'b', 'c' => 'd']);
        self::assertEquals(['b' => 'a', 'd' => 'c'], $seq->flip()->toArray());
    }

    public function test_flip_invalid_key_type(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->seq([true, false])->flip();
    }

    public function test_fold(): void
    {
        $reduced = $this->seq([])->fold(0, fn(int $i) => $i + 1);
        self::assertEquals(0, $reduced);

        $reduced = $this->seq(['a' => 1, 'b' => 2])->fold(new Collection(), static fn(Collection $c, int $i, string $k) => $c->set($k, $i * 2));
        self::assertEquals(['a' => 2, 'b' => 4], $reduced->toArray());

        $reduced = $this->seq(['a' => 1, 'b' => 2])->fold((object)[], static function ($c, $i, $k) {
            $c->$k = 0;
            return $c;
        });
        self::assertEquals(['a' => 0, 'b' => 0], (array) $reduced);

        $reduced = $this->seq([1, 2, 3])->fold(0, fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function test_getIterator(): void
    {
        $iterator = $this->seq([1])->getIterator();
        self::assertEquals([1], iterator_to_array($iterator));
    }

    public function test_groupBy(): void
    {
        $seq = $this->seq([1, 2, 3, 4, 5, 6]);
        $grouped = $seq->groupBy(fn(int $n): int => $n % 3)->toArrayRecursive();
        self::assertEquals([[2 => 3, 5 => 6], [0 => 1, 3 => 4], [1 => 2, 4 => 5]], $grouped);

        $seq = $this->seq([
            ['id' => 1],
            ['id' => 2],
            ['id' => 1],
        ]);
        self::assertEquals([
            1 => [
                0 => ['id' => 1],
                2 => ['id' => 1]
            ],
            2 => [
                1 => ['id' => 2]
            ]
        ], $seq->groupBy('id')->toArrayRecursive());
    }

    public function test_groupBy_missing_key(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "id"');
        $this->seq([['dummy' => 3]])->groupBy('id');
    }

    public function test_intersect(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([1], $seq->intersect([1])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $seq->intersect([1])->toArray());

        $seq = $this->seq([]);
        self::assertEquals([], $seq->intersect([1])->toArray());
    }

    public function test_intersectKeys(): void
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

    public function test_isAssoc(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isAssoc());

        $seq = $this->seq([1, 2]);
        self::assertFalse($seq->isAssoc());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->isAssoc());
    }

    public function test_isEmpty(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isEmpty());

        $seq = $this->seq([1, 2]);
        self::assertFalse($seq->isEmpty());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($seq->isEmpty());
    }

    public function test_isNotEmpty(): void
    {
        $seq = $this->seq([]);
        self::assertFalse($seq->isNotEmpty());

        $seq = $this->seq([1, 2]);
        self::assertTrue($seq->isNotEmpty());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->isNotEmpty());
    }

    public function test_isList(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isList());

        $seq = $this->seq([1, 2]);
        self::assertTrue($seq->isList());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($seq->isList());
    }

    public function test_join(): void
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

    public function test_jsonSerialize(): void
    {
        $seq = $this->seq([]);
        self::assertEquals([], $seq->jsonSerialize());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->jsonSerialize());
    }

    public function test_keyBy(): void
    {
        $seq = $this->seq([1, 2])->keyBy(fn($v) => 'a'.$v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $seq->toArray());

        $seq = $this->seq([['id' => 'b'], ['id' => 'c']])->keyBy(fn($v) => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $seq->toArray());
    }

    public function test_keyBy_with_duplicate_key(): void
    {
        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b'], ['id' => 'b']])->keyBy(fn($v) => $v['id']);
    }

    public function test_keyBy_with_overwritten_key(): void
    {
        $seq = $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $seq->toArray());

        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id']);
    }

    public function test_keyBy_with_invalid_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => false);
    }

    public function test_keys(): void
    {
        $keys = $this->seq([1,2])->keys();
        self::assertEquals([0,1], $keys->toArray());

        $keys = $this->seq(['a' => 1, 'b' => 2])->keys();
        self::assertEquals(['a', 'b'], $keys->toArray());
    }

    public function test_last(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->last());
        self::assertEquals(20, $seq->last(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->last(fn($v, $k) => $v === 20));
    }

    public function test_lastIndex(): void
    {
        $seq = $this->seq([10, 20, 20]);
        self::assertEquals(1, $seq->lastIndex(fn($v, $k) => $k === 1));
        self::assertEquals(2, $seq->lastIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastIndex(fn() => false));
    }

    public function test_lastKey(): void
    {
        $seq = $this->seq(['a' => 10, 'b' => 20, 'c' => 20]);
        self::assertEquals('c', $seq->lastKey());
        self::assertEquals('b', $seq->lastKey(fn($v, $k) => $k === 'b'));
        self::assertEquals('c', $seq->lastKey(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastKey(fn() => false));
    }

    public function test_lastOrNull(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->lastOrNull());
        self::assertEquals(20, $seq->lastOrNull(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->lastOrNull(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastOrNull(fn() => false));
    }

    public function test_lastOrFail_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->last();
    }

    public function test_lastOrFail_bad_condition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->last(fn(int $i) => $i > 2);
    }

    public function test_macro(): void
    {
        Sequence::macro('testMacro', static fn($num) => $num * 100);
        $seq = $this->seq([1]);
        self::assertEquals(200, $seq->testMacro(2));
    }

    public function test_macroExists(): void
    {
        $name = 'testMacro2'.mt_rand();
        self::assertFalse(Sequence::macroExists($name));
        Sequence::macro($name, static fn() => 1);
        self::assertTrue(Sequence::macroExists($name));
    }

    public function test_map(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([2, 4, 6], $seq->map(fn($i) => $i * 2)->toArray());
        self::assertEquals([0, 1, 2], $seq->map(fn($i, $k) => $k)->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 2, 'b' => 4, 'c' => 6], $seq->map(fn($i) => $i * 2)->toArray());
    }

    public function test_max(): void
    {
        $seq = $this->seq([1, 2, 3, 10, 1]);
        self::assertEquals(10, $seq->max());

        $seq = $this->seq([100, 2, 3, 10, 1]);
        self::assertEquals(100, $seq->max());

        $seq = $this->seq([1, 2, 3, 10, 1, -100, 90]);
        self::assertEquals(90, $seq->max());
    }

    public function test_maxBy(): void
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

    public function test_merge(): void
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

    public function test_mergeRecursive(): void
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

    public function test_min(): void
    {
        $seq = $this->seq([1, 2, 3, 10, -1]);
        self::assertEquals(-1, $seq->min());

        $seq = $this->seq([0, -1]);
        self::assertEquals(-1, $seq->min());

        $seq = $this->seq([1, 10, -100]);
        self::assertEquals(-100, $seq->min());
    }

    public function test_minBy(): void
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

    public function test_minMax(): void
    {
        $seq = $this->seq([1]);
        self::assertEquals(['min' => 1, 'max' => 1], $seq->minMax());

        $seq = $this->seq([1, 10, -100]);
        self::assertEquals(['min' => -100, 'max' => 10], $seq->minMax());
    }

    public function test_minMax_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->minMax();
    }

    public function test_notContains(): void
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

    public function test_notContainsKey(): void
    {
        self::assertTrue($this->seq([])->notContainsKey(0));
        self::assertTrue($this->seq([])->notContainsKey(1));
        self::assertTrue($this->seq(['b' => 1])->notContainsKey('a'));
        self::assertFalse($this->seq([1])->notContainsKey(0));
        self::assertFalse($this->seq([11 => 1])->notContainsKey(11));
        self::assertFalse($this->seq(['a' => 1, 0])->notContainsKey('a'));
    }

    public function test_notEquals(): void
    {
        self::assertTrue($this->seq([])->notEquals($this->seq([1])));
        self::assertTrue($this->seq([])->notEquals($this->seq([null])));
        self::assertTrue($this->seq(['b' => 1])->notEquals($this->seq(['a' => 1])));
        self::assertFalse($this->seq([1])->notEquals($this->seq([1])));
        self::assertFalse($this->seq(['a' => 1])->notEquals($this->seq(['a' => 1])));
    }
    public function test_only(): void
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

    public function test_only_WithUndefinedKey(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "a"');
        self::assertEquals([], $this->seq([])->only(['a'])->toArray());
    }

    public function test_prioritize(): void
    {
        $seq = $this->seq([1, 2, 3])->prioritize(fn(int $i) => $i === 2);
        self::assertEquals([2, 1, 3], $seq->values()->toArray());

        $seq = $this->seq(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2])->prioritize(fn($_, string $k) => strlen($k) > 1);
        self::assertEquals(['bc', 'de', 'a', 'b'], $seq->keys()->toArray());

        $seq = $this->seq([1, 2, 3])->prioritize(fn() => false);
        self::assertEquals([1, 2, 3], $seq->toArray());
    }

    public function test_reduce(): void
    {
        $reduced = $this->seq(['a' => 1])->reduce(fn(int $c, $i, $k) => 0);
        self::assertEquals(1, $reduced);

        $reduced = $this->seq(['a' => 1, 'b' => 2])->reduce(fn($val, $i) => $i * 2);
        self::assertEquals(4, $reduced);

        $reduced = $this->seq([1, 2, 3])->reduce(fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function test_reduce_unable_to_guess_initial(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array to contain at least 1 elements. Got: 0');
        $this->seq([])->reduce(fn($c, $i, $k) => $k);
    }

    public function test_repeat(): void
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

    public function test_repeat_negative_times(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');

        $seq = $this->seq([1])->repeat(-1);
        self::assertEquals([], $seq->toArray(), 'Repeat -1 times (throws error)');
    }

    public function test_reverse(): void
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

    public function test_rotate(): void
    {
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(1);
        self::assertEquals(['b' => 2, 'c' => 3, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(2);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(-1);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $seq->toArray());
    }

    public function test_sample(): void
    {
        mt_srand(100);
        self::assertEquals(8, $this->seq(range(0, 10))->sample());
    }

    public function test_sample_Empty(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('array_rand(): Argument #1 ($array) cannot be empty');
        $this->seq([])->sample();
    }

    public function test_sampleMany(): void
    {
        mt_srand(100);
        self::assertEquals([8 => 8, 9 => 9], $this->seq(range(0, 10))->sampleMany(2)->toArray());
    }

    public function test_satisfyAll(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->satisfyAll(static fn($v) => is_int($v)));

        $seq = $this->seq([1, 2, 3]);
        self::assertTrue($seq->satisfyAll(static fn($v) => is_int($v)));

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertTrue($seq->satisfyAll(static fn($v, $k) => is_string($k)));

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 4, '1']);
        self::assertFalse($seq->satisfyAll(static fn($k) => is_string($k)));
    }

    public function test_satisfyAny(): void
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

    public function test_shuffle(): void
    {
        mt_srand(100);
        self::assertEquals([1, 2, 4, 3, 2], $this->seq([1, 2, 2, 3, 4])->shuffle()->toArray());
        self::assertSame(['a' => 1, 'c' => 3, 'b' => 2, 'd' => 4], $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->shuffle()->toArray());
    }

    public function test_sole(): void
    {
        self::assertEquals(1, $this->seq([1])->sole());
        self::assertEquals(1, $this->seq(['a' => 1])->sole());
        self::assertEquals(2, $this->seq([1, 2, 3])->sole(fn(int $i) => $i === 2));
    }

    public function test_sole_zero_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 0 given.');
        $this->seq([])->sole();
    }

    public function test_sole_more_than_one_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        $this->seq([1, 2])->sole();
    }

    public function test_sort(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sort()->values();
        self::assertEquals([1, 2, 3, 4], $seq->toArray());

        $seq = $this->seq(['30', '2', '100'])->sort(SORT_NATURAL)->values();
        self::assertEquals(['2', '30', '100'], $seq->toArray());

        $seq = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sort();
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $seq->toArray());
    }

    public function test_sortBy(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortBy(fn($v) => $v)->values();
        self::assertEquals([1, 2, 3, 4], $seq->toArray());

        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $seq->toArray());
    }

    public function test_sortByDesc(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortByDesc(fn($v) => $v)->values();
        self::assertEquals([4, 3, 2, 1], $seq->toArray());

        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $seq->toArray());
    }

    public function test_sortDesc(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortDesc()->values();
        self::assertEquals([4, 3, 2, 1], $seq->toArray());

        $seq = $this->seq(['30', '100', '2'])->sortDesc(SORT_NATURAL)->values();
        self::assertEquals(['100', '30', '2'], $seq->toArray());

        $seq = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sortDesc();
        self::assertEquals(['a' => 3, 'c' => 2, 'b' => 1], $seq->toArray());
    }

    public function test_sortKeys(): void
    {
        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKey();
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $seq->toArray());

        $seq = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKey(SORT_NATURAL);
        self::assertEquals(['2' => 0, '30' => 2, '100' => 1], $seq->toArray());
    }

    public function test_sortKeysDesc(): void
    {
        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKeyDesc();
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKeyDesc(SORT_NATURAL);
        self::assertEquals(['100' => 1, '30' => 2, '2' => 0], $seq->toArray());
    }

    public function test_sortWith(): void
    {
        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->sortWith(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $seq->toArray());
    }

    public function test_sortWithKey(): void
    {
        $seq = $this->seq([1 => 'a', 3 => 'b', 2 => 'c'])->sortWithKey(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals([1 => 'a', 2 => 'c', 3 => 'b'], $seq->toArray());
    }

    public function test_sum(): void
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

    public function test_sum_throw_on_sum_of_string(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unsupported operand types: int + string');
        $this->seq(['a', 'b'])->sum();
    }

    public function test_take(): void
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

    public function test_takeUntil(): void
    {
        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => $v > 2);
        self::assertEquals(['b' => 1], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => false);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => true);
        self::assertEquals([], $seq->toArray());
    }

    public function test_takeWhile(): void
    {
        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 4])->takeWhile(fn($v) => $v < 4);
        self::assertEquals(['b' => 1, 'a' => 3], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => false);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => true);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $seq->toArray());
    }

    public function test_tally(): void
    {
        $seq = $this->seq([1, 1, 1, 2, 3, 3])->tally();
        self::assertEquals([1 => 3, 2 => 1, 3 => 2], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 1, 'c' => 1])->tally();
        self::assertEquals([1 => 3], $seq->toArray());
    }

    public function test_tap(): void
    {
        $seq = $this->seq([1, 2])->tap(fn() => 100);
        self::assertEquals([1, 2], $seq->toArray());

        $cnt = 0;
        $seq = $this->seq([])->tap(function() use (&$cnt) { ++$cnt; });
        self::assertEquals([], $seq->toArray());
        self::assertEquals(1, $cnt);
    }

    public function test_toArray(): void
    {
        self::assertEquals([], $this->seq([])->toArray());
        self::assertEquals([1, 2], $this->seq([1, 2])->toArray());
        self::assertEquals(['a' => 1], $this->seq(['a' => 1])->toArray());

        $inner = $this->seq([1, 2]);
        self::assertEquals(['a' => $inner], $this->seq(['a' => $inner])->toArray());
    }

    public function test_toArrayRecursive(): void
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

    public function test_toJson(): void
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

    public function test_toUrlQuery(): void
    {
        $query = $this->seq(['a' => 1])->toUrlQuery('t');
        self::assertEquals(urlencode('t[a]').'=1', $query);

        $query = $this->seq(['a' => 1, 'b' => 2])->toUrlQuery();
        self::assertEquals("a=1&b=2", $query);
    }

    public function test_union(): void
    {
        $seq = $this->seq([])->union([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['a' => 1])->union(['a' => 2]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1]])->union(['a' => ['c' => 2]]);
        self::assertEquals(['a' => ['b' => 1]], $seq->toArray());
    }

    public function test_unionRecursive(): void
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

    public function test_unique(): void
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

        $seq = $this->seq($values)->repeat(2)->unique();
        self::assertEquals($values, $seq->toArray());
    }

    public function test_uniqueBy(): void
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

    public function test_values(): void
    {
        $seq = $this->seq([])->values();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 1, 2])->values()->reverse();
        self::assertEquals([2, 1, 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2])->values();
        self::assertEquals([1, 2], $seq->toArray());
    }
}
