<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Kirameki\Core\Config;
use Redis;
use RedisArray;
use RedisCluster;

interface Adapter
{
    /**
     * @return Redis|RedisCluster|RedisArray
     */
    public function getClient(): object;

    /**
     * @return Config
     */
    public function getConfig(): Config;

    /**
     * @return string
     */
    public function getPrefix(): string;

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): static;

    /**
     * @return $this
     */
    public function connect(): static;

    /**
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * @return $this
     */
    public function reconnect(): static;

    /**
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * @param string $name
     * @param mixed $args
     * @return mixed
     */
    public function command(string $name, mixed ...$args): mixed;
}
