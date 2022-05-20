<?php declare(strict_types=1);

namespace Tests\Kirameki\Redis;

use Kirameki\Redis\Exceptions\CommandException;
use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Testing\Concerns\UsesRedis;
use stdClass;
use Tests\Kirameki\TestCase;
use Webmozart\Assert\InvalidArgumentException;
use function array_keys;
use function mt_rand;

class ConnectionTest extends TestCase
{
    use UsesRedis;

    public function test_invalid_connection(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('php_network_getaddresses: getaddrinfo for redis-ng failed: Name does not resolve');
        $this->createRedisConnection('phpredis-ng')->exists('a');
    }

    # region CONNECTION ------------------------------------------------------------------------------------------------

    public function test_string_echo(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals('hi', $conn->echo('hi'));
    }

    public function test_ping(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertTrue($conn->ping());
    }

    public function test_select(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertTrue($conn->select(1));
        self::assertEquals(1, $conn->clientInfo()['db']);
    }

    # endregion CONNECTION ---------------------------------------------------------------------------------------------

    # region SERVER ----------------------------------------------------------------------------------------------------

    public function test_server_dbSize(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(0, $conn->dbSize());
        $conn->mSet(['a' => 1, 'b' => 2]);
        self::assertEquals(2, $conn->dbSize());
    }

    # endregion SERVER -------------------------------------------------------------------------------------------------

    # region KEY -------------------------------------------------------------------------------------------------------

    public function test_del(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $data = ['a' => 1, 'b' => 2];
        $keys = array_keys($data);
        $mSetResult = $conn->mSet($data);
        $sets = $conn->mGet(...$keys);

        self::assertTrue($mSetResult);
        self::assertEquals(1, $sets['a']);
        self::assertEquals(2, $sets['b']);

        $conn->del(...$keys);

        // check removed
        $result = $conn->mGet(...$keys);
        self::assertFalse($result['a']);
        self::assertFalse($result['b']);
    }

    public function test_exists(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $data = ['a' => 1, 'b' => 2, 'c' => false, 'd' => null];
        $keys = array_keys($data);
        $conn->mSet($data);

        // mixed result
        $result = $conn->exists(...$keys, ...['f']);
        self::assertEquals(4, $result);

        // nothing exists
        $result = $conn->exists('x', 'y', 'z');
        self::assertEquals(0, $result);
    }

    public function test_exists_without_args(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: array');
        $conn = $this->createRedisConnection('phpredis');
        $conn->exists();
    }

    public function test_scan(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $data = ['a1' => 1, 'a2' => 2, '_a3' => false, 'a4' => null];
        $conn->mSet($data);

        // full scan
        self::assertEquals(['_a3', 'a1', 'a2', 'a4'], $conn->scan()->toSequence()->sort()->values()->toArray());

        // scan with count
        self::assertEquals(['_a3', 'a1', 'a2', 'a4'], $conn->scan(count: 1)->toSequence()->sort()->values()->toArray());

        // filtered with wild card
        self::assertEquals(['a1', 'a2', 'a4'], $conn->scan('a*')->toSequence()->sort()->values()->toArray());

        // filtered with prefix
        $conn->setPrefix('conn1:');
        $conn->mSet(['a5' => 5]);
        self::assertEquals(['a5'], $conn->scan('a*')->toArray());
        self::assertEquals(['a5'], $conn->scan()->toArray());

        // filtered with prefix and return prefixed
        $conn->setPrefix('conn1:');
        self::assertEquals(['conn1:a5'], $conn->scan('a*', prefixed: true)->toArray());
    }

    # endregion KEY ----------------------------------------------------------------------------------------------------

    # region STRING ----------------------------------------------------------------------------------------------------

    public function test_string_decr(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(-1, $conn->decr('d'));
        self::assertEquals(-3, $conn->decr('d', 2));
        self::assertEquals(-1, $conn->decr('d', -2));
    }

    public function test_string_decrByFloat(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(-1, $conn->decrByFloat('d', 1));
        self::assertEquals(-3.2, $conn->decrByFloat('d', 2.2));
        self::assertEquals(-1, $conn->decrByFloat('d', -2.2));
    }

    public function test_string_get(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $conn->mSet(['d' => 'abc', 'e' => null]);
        self::assertEquals('abc', $conn->get('d'));
        self::assertEquals(null, $conn->get('e'));
        self::assertEquals(false, $conn->get('f'));
    }

    public function test_string_incr(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(1, $conn->incr('d'));
        self::assertEquals(3, $conn->incr('d', 2));
        self::assertEquals(1, $conn->incr('d', -2));
    }

    public function test_string_incrByFloat(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(1, $conn->incrByFloat('d', 1));
        self::assertEquals(3.2, $conn->incrByFloat('d', 2.2));
        self::assertEquals(1, $conn->incrByFloat('d', -2.2));
    }

    public function test_string_mGet(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $pairs = ['a' => true, 'b' => mt_rand(), 'c' => 0.01, 'd' => 'abc', 'e' => null];
        $conn->mSet($pairs);
        self::assertEquals($pairs, $conn->mGet(...array_keys($pairs)));
    }

    public function test_string_mGet_with_array(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $pairs = ['arr' => ['a' => 1, 'b' => 2]];
        $conn->mSet($pairs);
        self::assertEquals($pairs, $conn->mGet('arr'));
    }

    public function test_string_mGet_with_object(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $object = new stdClass();
        $object->a = 1;
        $pairs = ['o' => $object];
        $conn->mSet($pairs);
        self::assertEquals($object->a, $conn->mGet('o')['o']->a);
    }

    public function test_string_mGet_without_args(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: array');
        $conn = $this->createRedisConnection('phpredis');
        $conn->mGet();
    }

    public function test_string_mSet(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $pairs = ['a1' => mt_rand(), 'a2' => mt_rand()];
        self::assertTrue($conn->mSet($pairs));
        self::assertEquals($pairs, $conn->mGet('a1', 'a2'));
    }

    public function test_string_mSet_with_array(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $pairs = ['arr' => ['a' => 1, 'b' => 2]];
        $conn->mSet($pairs);
        self::assertEquals($pairs, $conn->mGet('arr'));
    }

    public function test_string_mSet_with_object(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $object = new stdClass();
        $object->a = 1;
        $pairs = ['o' => $object];
        $conn->mSet($pairs);
        self::assertEquals($object->a, $conn->mGet('o')['o']->a);
    }

    public function test_string_mSet_without_args(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: array');
        $conn = $this->createRedisConnection('phpredis');
        $conn->mSet([]);
    }

    public function test_string_randomKey(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(null, $conn->randomKey());
        $conn->set('test', 1);
        self::assertEquals('test', $conn->randomKey());
    }

    public function test_string_rename(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $conn->set('test', 1);
        self::assertTrue($conn->rename('test', 'renamed'));
    }

    public function test_string_rename_key_not_exists(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('ERR no such key');
        $conn = $this->createRedisConnection('phpredis');
        self::assertFalse($conn->rename('miss', 'renamed'));
    }

    # endregion STRING -------------------------------------------------------------------------------------------------

    # region LIST ------------------------------------------------------------------------------------------------------

    public function test_list_blPop(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(2, $conn->lPush('l', 'abc', 1));
        self::assertEquals(['l' => 1], $conn->blPop(['l'], 100));
        self::assertEquals(['l' => 'abc'], $conn->blPop(['l'], 100));
        if ($this->includeSlowTests()) {
            self::assertEquals(null, $conn->blPop(['l'], 1));
        }
    }

    public function test_list_blPop_key_not_a_list(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key holding the wrong kind of value');
        $conn = $this->createRedisConnection('phpredis');
        $conn->set('l', 1);
        $conn->blPop(['l'], 1);
    }

    public function test_list_lIndex(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(2, $conn->lPush('l', 'abc', 1));
        self::assertEquals('abc', $conn->lIndex('l', 1));
        self::assertEquals('abc', $conn->lIndex('l', -1));
        self::assertFalse($conn->lIndex('l', 2)); // no index found
        self::assertFalse($conn->lIndex('m', -1)); // no key found
    }

    public function test_list_lIndex_key_not_a_list(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key holding the wrong kind of value');
        $conn = $this->createRedisConnection('phpredis');
        $conn->set('l', 1);
        $conn->lIndex('l', 2);
    }

    public function test_list_lPush(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(2, $conn->lPush('l', 'abc', 1));
        self::assertEquals(3, $conn->lPush('l', 2));
        self::assertEquals('abc', $conn->lIndex('l', 2));
        self::assertEquals(1, $conn->lIndex('l', 1));
        self::assertEquals(2, $conn->lIndex('l', 0));
    }

    public function test_list_lPush_key_not_a_list(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key holding the wrong kind of value');
        $conn = $this->createRedisConnection('phpredis');
        $conn->set('l', 1);
        $conn->lPush('l', 2);
    }

    public function test_list_rPush(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        self::assertEquals(2, $conn->rPush('l', 'abc', 1));
        self::assertEquals(3, $conn->rPush('l', 2));
        self::assertEquals('abc', $conn->lIndex('l', 0));
        self::assertEquals(1, $conn->lIndex('l', 1));
        self::assertEquals(2, $conn->lIndex('l', 2));
    }

    public function test_list_rPush_key_not_a_list(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key holding the wrong kind of value');
        $conn = $this->createRedisConnection('phpredis');
        $conn->set('l', 1);
        $conn->rPush('l', 2);
    }

    # endregion LIST ---------------------------------------------------------------------------------------------------
}
