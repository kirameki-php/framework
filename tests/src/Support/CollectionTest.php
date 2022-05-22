<?php declare(strict_types=1);

namespace Tests\Kirameki\Support;

use ErrorException;
use InvalidArgumentException;
use Kirameki\Support\Collection;
use RuntimeException;
use Tests\Kirameki\TestCase;
use TypeError;
use function collect;
use function dd;

class CollectionTest extends TestCase
{
    public function test__construct(): void
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

    public function test___construct_bad_argument(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument #1 ($items) must be of type ?iterable, int given');
        new Collection(1);
    }

    public function test_copy(): void
    {
        // empty but not same instance
        $empty = collect();
        $clone = $empty->copy();
        self::assertNotSame($empty, $clone);
        self::assertEmpty($clone);

        // copy sequence
        $seq = collect([3, 4]);
        $clone = $seq->copy();
        self::assertNotSame($seq, $clone);
        self::assertEquals([3, 4], $seq->toArray());

        // copy assoc
        $seq = collect(['a' => 3, 'b' => 4]);
        $clone = $seq->copy();
        self::assertNotSame($seq, $clone);
        self::assertEquals(['a' => 3, 'b' => 4], $seq->toArray());
    }

    public function test_append(): void
    {
        $collect = collect([1, 2]);
        self::assertSame($collect, $collect->append(3));
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = collect([1, 2]);
        self::assertSame($collect, $collect->append(3));
        self::assertEquals([1, 2, 3], $collect->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertSame($collect, $collect->append('b'));
        self::assertEquals(['a' => 1, 'b' => 2, 'b'], $collect->toArray());
    }

    public function test_get(): void
    {
        $collect = collect([1, 2]);
        self::assertEquals(2, $collect->get(1));

        $collect = collect(['a' => [1, 'b' => 2, 'c' => ['d' => 3]], 'c' => 'd', 'e' => []]);
        // get existing data
        self::assertEquals([1, 'b' => 2, 'c' => ['d' => 3]], $collect->get('a'));
        self::assertEquals('d', $collect->get('c'));
    }

    public function test_get_invalid_key_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Undefined array key 2');
        collect([1, 2])->get(2);
    }

    public function test_getOr(): void
    {
        $collect = collect(['a' => [1, 'b' => 2, 'c' => ['d' => 3]], 'c' => 'd', 'e' => []]);
        // get existing data
        self::assertEquals([1, 'b' => 2, 'c' => ['d' => 3]], $collect->get('a'));
        self::assertEquals('d', $collect->getOr('c', null));
        self::assertEquals(null, $collect->getOr(0, null));
    }

    public function test_insertAt(): void
    {
        $collect = collect([1, 2]);
        self::assertEquals(['a', 1, 2], $collect->insertAt(0, 'a')->toArray());

        $collect = collect([1, 2]);
        self::assertEquals([1, 'a', 2], $collect->insertAt(1, 'a')->toArray());

        $collect = collect([1, 2]);
        self::assertEquals([1, 2, 'a'], $collect->insertAt(10, 'a')->toArray());

        $collect = collect([1, 2, 3, 4]);
        self::assertEquals([1, 2, 3, 4, 'a'], $collect->insertAt(-1, 'a')->toArray());

        $collect = collect([1, 2, 3, 4]);
        self::assertEquals([1, 2, 3, 'a', 4], $collect->insertAt(-2, 'a')->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'c' => 3, 'b' => 2], $collect->insertAt(1, c: 3)->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 3], $collect->insertAt(1, b: 3)->toArray());
    }

    public function test_offsetExists(): void
    {
        $seq = collect([1, 2]);
        self::assertTrue(isset($seq[0]));

        $assoc = collect(['a' => 1, 'b' => 2]);
        self::assertTrue(isset($assoc['b']));

        $assoc = collect([]);
        self::assertFalse(isset($assoc['a']));
    }

    public function test_offsetGet(): void
    {
        $seq = collect([1, 2]);
        self::assertEquals(1, $seq[0]);

        $assoc = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $assoc['b']);
    }

    public function test_offsetGet_undefined_key(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "e"');
        collect(['a' => 1, 'b' => 2])['e'];
    }

    public function test_offsetSet(): void
    {
        // push number
        $seq = collect([1, 2]);
        $seq[] = 3;
        self::assertEquals([1, 2, 3], $seq->toArray());

        // skip number from 0, 1, to 3
        $seq = collect([1, 2]);
        $seq[3] = 3;
        self::assertEquals([1, 2, 3 => 3], $seq->toArray());

        // set offset with string
        $assoc = collect(['a' => 1, 'b' => 2]);
        $assoc['c'] = 3;
        self::assertEquals(3, $assoc['c']);
    }

    public function test_offsetSet_bool_as_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        collect([])[true]= 1;
    }

    public function test_offsetSet_float_as_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        collect([])[1.1]= 1;
    }

    public function test_offsetUnset(): void
    {
        $seq = collect([1, 2]);
        unset($seq[0]);
        self::assertEquals([1 => 2], $seq->toArray());

        $assoc = collect(['a' => 1, 'b' => 2]);
        unset($assoc['b']);
        self::assertEquals(['a' => 1], $assoc->toArray());

        $assoc = collect([]);
        unset($assoc['b']);
        self::assertEquals([], $assoc->toArray());
    }

    public function test_pad(): void
    {
        $collect = collect([1, 2]);
        self::assertEquals([1, 2], $collect->pad(0, 9)->toArray());
        self::assertEquals([1, 2], $collect->pad(2, 9)->toArray());
        self::assertEquals([1, 2], $collect->pad(-1, 9)->toArray());
        self::assertEquals([1, 2, 9], $collect->pad(3, 9)->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2, 0 => 9], $collect->pad(3, 9)->toArray());

        self::assertEquals([9, 9, 9], collect([])->pad(3, 9)->toArray());
    }

    public function test_pop(): void
    {
        $collect = collect([1, 2]);
        self::assertEquals(2, $collect->pop());
        self::assertEquals([1], $collect->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $collect->pop());
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function test_popMany(): void
    {
        $collect = collect([1, 2]);
        self::assertEquals([2], $collect->popMany(1)->toArray());
        self::assertEquals([1], $collect->toArray());
        $collect = collect([1, 2]);
        self::assertEquals([1, 2], $collect->popMany(2)->toArray());
        self::assertEquals([], $collect->toArray());
        $collect = collect([1, 2]);
        self::assertEquals([1, 2], $collect->popMany(3)->toArray());
        self::assertEquals([], $collect->toArray());
    }

    public function test_prepend(): void
    {
        $collect = collect([])->prepend(1);
        self::assertEquals([1], $collect->toArray());

        $collect = collect([1, 1])->prepend(0);
        self::assertEquals([0, 1, 1], $collect->toArray());

        $collect = collect(['a' => 1])->prepend(1, 2);
        self::assertEquals([1, 2, 'a' => 1], $collect->toArray());
    }

    public function test_pull(): void
    {
        $collect = collect([1, 2, 3]);
        self::assertEquals(2, $collect->pull(1));
        self::assertEquals([1, 3], $collect->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $collect->pull('b'));
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function test_pull_pull_on_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tried to pull undefined array key "1"');
        collect([])->pull(1);
    }

    public function test_pull_pull_undefined_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tried to pull undefined array key "c"');
        collect(['a' => 1, 'b' => 2])->pull('c');
    }

    public function test_pullOr(): void
    {
        $collect = collect([]);
        self::assertEquals(null, $collect->pullOr(1, null));

        $collect = collect([1, 2, 3]);
        self::assertEquals(2, $collect->pullOr(1, null));
        self::assertEquals([1, 3], $collect->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $collect->pullOr('b', null));
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function test_pullMany(): void
    {
        $collect = collect([1, 2, 3]);
        $pulled = $collect->pullMany(0, 1);
        self::assertEquals([3], $collect->toArray());
        self::assertEquals([1, 2], $pulled->toArray());

        $collect = collect(['a' => 1, 'b' => 2, 'c' => 3]);
        $pulled = $collect->pullMany('a', 'c');
        self::assertEquals(['b' => 2], $collect->toArray());
        self::assertEquals(['a' => 1, 'c' => 3], $pulled->toArray());

        $collect = collect(['a' => 1]);
        $pulled = $collect->pullMany('a', 'c');
        self::assertEquals([], $collect->toArray());
        self::assertEquals(['a' => 1], $pulled->toArray());
    }

    public function test_remove(): void
    {
        $collect = collect([]);
        self::assertEquals([], $collect->remove(1));

        $collect = collect([1]);
        self::assertEquals([0], $collect->remove(1));
        self::assertEquals([], $collect->toArray());

        $collect = collect([1, 1]);
        self::assertEquals([0, 1], $collect->remove(1));
        self::assertEquals([], $collect->toArray());

        $collect = collect([1, 1]);
        self::assertEquals([0], $collect->remove(1, 1));
        self::assertEquals([1], $collect->toArray());

        $collect = collect(['a' => 1]);
        self::assertEquals(['a'], $collect->remove(1));
        self::assertEquals([], $collect->toArray());

        $collect = collect(['a' => 1]);
        self::assertEquals([], $collect->remove(1, -1));
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function test_removeKey(): void
    {
        $collect = collect([]);
        self::assertEquals(false, $collect->removeKey(1));

        $collect = collect([1]);
        self::assertEquals(true, $collect->removeKey(0));
        self::assertEquals([], $collect->toArray());

        $collect = collect(['a' => 1]);
        self::assertEquals(true, $collect->removeKey('a'));
        self::assertEquals([], $collect->toArray());

        $collect = collect(['a' => 1]);
        self::assertEquals(false, $collect->removeKey('b'));
        self::assertEquals(['a' => 1], $collect->toArray());
    }

    public function test_set(): void
    {
        self::assertEquals(['a' => 1], collect([])->set('a', 1)->toArray());
        self::assertEquals(['a' => 1], collect([])->set('a', 0)->set('a', 1)->toArray());
        self::assertEquals(['a' => null], collect([])->set('a', null)->toArray());
    }

    public function test_setIfExists(): void
    {
        self::assertEquals(
            [],
            collect([])->setIfExists('a', 1)->toArray(),
            'Set when not exists'
        );

        self::assertEquals(
            [],
            collect([])->setIfExists('a', 1)->setIfExists('a', 2)->toArray(),
            'Set when not exists twice on non existing'
        );

        self::assertEquals(
            ['a' => 2],
            collect([])->set('a', null)->setIfExists('a', 1)->setIfExists('a', 2)->toArray(),
            'Set when not exists twice on existing'
        );

        self::assertEquals(
            ['a' => 1],
            collect(['a' => 0])->setIfExists('a', 1)->toArray(),
            '$value1 => $value2'
        );

        self::assertEquals(
            ['a' => 1],
            collect([])->set('a', null)->setIfExists('a', 1)->toArray(),
            'null => $value',
        );

        self::assertEquals(
            ['a' => null],
            collect([])->set('a', 1)->setIfExists('a', null)->toArray(),
            '$value => null'
        );

        $result = false;
        collect([])->setIfExists('a', 1, $result)->toArray();
        self::assertFalse($result, 'Result for no previous value');

        $result = false;
        collect(['a' => 0])->setIfExists('a', 1, $result)->toArray();
        self::assertTrue((bool)$result, 'Result for value already existing');
    }

    public function test_setIfNotExists(): void
    {
        self::assertEquals(
            ['a' => 1],
            collect([])->setIfNotExists('a', 1)->toArray(),
            'Set on non-existing'
        );

        self::assertEquals(
            ['a' => 0],
            collect([])->setIfNotExists('a', 0)->setIfNotExists('a', 1)->toArray(),
            'Set on non existing twice',
        );

        self::assertEquals(
            ['a' => null],
            collect([])->setIfNotExists('a', null)->toArray(),
            'Set null'
        );

        $result = false;
        collect([])->setIfNotExists('a', 1, $result)->toArray();
        self::assertTrue((bool)$result, 'Result for no previous value');

        $result = false;
        collect(['a' => 0])->setIfNotExists('a', 1, $result)->toArray();
        self::assertFalse($result, 'Result for value already exiting');
    }

    public function test_shift(): void
    {
        self::assertEquals(1, collect([1, 2])->shift());
        self::assertEquals(null, collect([])->shift());
        self::assertEquals(1, collect(['a' => 1, 2])->shift());
        self::assertEquals(['b' => 1], collect(['a' => ['b' => 1]])->shift());
    }

    public function test_shiftMany(): void
    {
        $collect = collect([1, 2]);
        self::assertEquals([1], $collect->shiftMany(1)->toArray());
        self::assertEquals([2], $collect->toArray());

        $collect = collect([1, 2]);
        self::assertEquals([1, 2], $collect->shiftMany(2)->toArray());
        self::assertEquals([], $collect->toArray());

        $collect = collect([1, 2]);
        self::assertEquals([1, 2], $collect->shiftMany(3)->toArray());
        self::assertEquals([], $collect->toArray());

        $collect = collect(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1], $collect->shiftMany(1)->toArray());
        self::assertEquals(['b' => 2], $collect->toArray());
    }
}
