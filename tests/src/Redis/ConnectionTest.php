<?php declare(strict_types=1);

namespace Tests\Kirameki\Redis;

use Kirameki\Testing\Concerns\UsesRedis;
use Tests\Kirameki\TestCase;
use function array_keys;
use function dump;

class ConnectionTest extends TestCase
{
    use UsesRedis;

    public function testDel(): void
    {
        $conn = $this->createRedisConnection('cache');

        $data = ['a' => 1, 'b' => 2];
        $keys = array_keys($data);
        $conn->mSet($data);

        $sets = $conn->mget(...$keys);

        $this->assertEquals(1, $sets[0]);
        $this->assertEquals(2, $sets[1]);

        $conn->del(...$keys);

        // check removed
        $result = $conn->mget(...$keys);
        $this->assertFalse($result[0]);
        $this->assertFalse($result[1]);
    }
}
