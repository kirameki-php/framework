<?php declare(strict_types=1);

namespace Tests\Kirameki\Redis;

use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Testing\Concerns\UsesRedis;
use Tests\Kirameki\TestCase;
use function array_keys;
use function dump;

class ConnectionTest extends TestCase
{
    use UsesRedis;

    public function testInvalidConnection(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('php_network_getaddresses: getaddrinfo for redis-ng failed: Name does not resolve');
        $this->createRedisConnection('phpredis-ng')->exists('a');
    }

    public function testDel(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $data = ['a' => 1, 'b' => 2];
        $keys = array_keys($data);
        $mSetResult = $conn->mSet($data);
        $sets = $conn->mGet(...$keys);

        $this->assertTrue($mSetResult);
        $this->assertEquals(1, $sets[0]);
        $this->assertEquals(2, $sets[1]);

        $conn->del(...$keys);

        // check removed
        $result = $conn->mGet(...$keys);
        $this->assertFalse($result[0]);
        $this->assertFalse($result[1]);
    }

    public function testEcho(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $this->assertEquals('hi', $conn->echo('hi'));
    }

    public function testExists(): void
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

    public function testPing(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $this->assertTrue($conn->ping());
    }

    public function testSelect(): void
    {
        $conn = $this->createRedisConnection('phpredis');
        $this->assertTrue($conn->select(1));
        $this->assertEquals(1, $conn->clientInfo()['db']);
    }

    public function testScan(): void
    {
        $conn = $this->createRedisConnection('phpredis');

        $sets = [];
        for($i = 0; $i < 50; $i++) {
            $sets['a' . $i] = 1;
        }

        $conn->mSet($sets);

        $result = $conn->scan();

        dump("SCAN ", $result->toArray());

        dump("DEL ", $conn->del(...$result->toArray()));

        dump("COUNT ", $result->count());

        $result1 = $conn->scan()->toArray();

        dump($result1);
    }

}
