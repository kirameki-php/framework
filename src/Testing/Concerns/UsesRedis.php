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
                // Prefix must be cleared before flushing, otherwise it will only flush ones with prefixed keys.
                $connection->setPrefix('');
                $connection->flushKeys();
                $connection->select(0);
            }
        });

        return $connection;
    }
}
