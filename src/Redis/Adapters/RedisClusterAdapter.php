<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Redis;
use RedisCluster;
use function assert;

class RedisClusterAdapter extends Adapter
{
    /**
     * @inheritDoc
     * @return RedisCluster
     */
    public function getConnectedClient(): object
    {
        if ($this->redis === null) {
            $config = $this->config;
            $seeds = $config->getArray('seeds');
            $timeout = $config->getFloatOr('timeout', default: 0.0);
            $persistent = $config->getBoolOr('persistent', default: false);
            $password = $config->getStringOrNull('password');
            $prefix = $config->getStringOr('prefix', default: '');
            $redis = new RedisCluster(null, $seeds, $timeout, 0.0, $persistent, $password);

            $redis->setOption(RedisCluster::OPT_PREFIX, $prefix);
            $redis->setOption(RedisCluster::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

            $this->redis = $redis;
        }
        assert($this->redis instanceof RedisCluster);
        return $this->redis;
    }

    /**
     * @return list<Redis>
     */
    public function connectToNodes(): array
    {
        $timeout = $this->config->getFloatOr('timeout', default: 0.0);

        $nodes = [];
        foreach ($this->getConnectedClient()->_masters() as $data) {
            $nodes[] = $this->connectDirect($data[0], $data[1], $timeout, false);
        }
        return $nodes;
    }
}
