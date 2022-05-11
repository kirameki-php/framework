<?php declare(strict_types=1);

namespace Tests\Kirameki\Redis;

use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Testing\Concerns\UsesRedis;
use Tests\Kirameki\TestCase;
use Webmozart\Assert\InvalidArgumentException;
use function array_keys;
use function dump;
use function mt_rand;
use function rand;

class ConnectionTest extends TestCase
{
    use UsesRedis;

    public function test_invalid_connection(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('php_network_getaddresses: getaddrinfo for redis-ng failed: Name does not resolve');
        $this->createRedisConnection('phpredis-ng')->exists('a');
    }

    public function test_del(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $data = ['a' => 1, 'b' => 2];
        $keys = array_keys($data);
        $mSetResult = $conn->mSet($data);
        $sets = $conn->mGet(...$keys);

        $this->assertTrue($mSetResult);
        $this->assertEquals(1, $sets['a']);
        $this->assertEquals(2, $sets['b']);

        $conn->del(...$keys);

        // check removed
        $result = $conn->mGet(...$keys);
        $this->assertFalse($result['a']);
        $this->assertFalse($result['b']);
    }

    public function test_echo(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $this->assertEquals('hi', $conn->echo('hi'));
    }

    public function test_exists(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $data = ['a' => 1, 'b' => 2, 'c' => false, 'd' => null];
        $keys = array_keys($data);
        $conn->mSet($data);

        // mixed result
        $result = $conn->exists(...$keys, ...['f']);
        $this->assertEquals(4, $result);

        // nothing exists
        $result = $conn->exists('x', 'y', 'z');
        $this->assertEquals(0, $result);
    }

    public function test_exists_without_args(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: array');
        $conn = $this->createRedisConnection('phpredis');
        $conn->exists();
    }

    public function test_mGet(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $pairs = ['a1' => mt_rand(), 'a2' => mt_rand()];
        $conn->mSet($pairs);
        $this->assertEquals($pairs, $conn->mGet('a1', 'a2'));
    }

    public function test_mGet_without_args(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: array');
        $conn = $this->createRedisConnection('phpredis');
        $conn->mGet();
    }

    public function test_ping(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $this->assertTrue($conn->ping());
    }

    public function test_select(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $this->assertTrue($conn->select(1));
        $this->assertEquals(1, $conn->clientInfo()['db']);
    }
}
