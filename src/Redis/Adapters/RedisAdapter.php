<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Redis;
use function assert;

class RedisAdapter extends Adapter
{
    /**
     * @return Redis
     */
    public function getConnectedClient(): object
    {
        if ($this->redis === null) {
            $config = $this->config;
            $this->redis = $this->connectDirect(
                $config->getStringOr('host', default: 'localhost'),
                $config->getIntOr('port', default: 6379),
                $config->getFloatOr('timeout', default: 0.0),
                $config->getBoolOr('persistent', default: false),
            );
        }
        assert($this->redis instanceof Redis);
        return $this->redis;
    }

    /**
     * @return list<Redis>
     */
    public function connectToNodes(): array
    {
        $config = $this->config;
        $host = $config->getStringOr('host', default: 'localhost');
        $port = $config->getIntOr('port', default: 6379);
        $timeout = $config->getFloatOr('timeout', default: 0.0);

        return [
            $this->connectDirect($host, $port, $timeout, false)
        ];
    }
}
