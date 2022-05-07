<?php declare(strict_types=1);

namespace Kirameki\Testing\Concerns;

use Kirameki\Redis\Connection;
use Kirameki\Redis\RedisManager;
use Kirameki\Testing\TestCase;

/**
 * @mixin TestCase
 */
trait UsesRedis
{
    public function createRedisConnection(string $name): Connection
    {
        $redis = $this->app->get(RedisManager::class);

        $connection = $redis->using($name);

        $this->runAfterTearDown(static function () use ($connection): void {
            if ($connection->isConnected()) {
                $connection->flush();
            }
        });

        return $connection;
    }
}
