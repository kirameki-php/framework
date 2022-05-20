<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Generator;
use Kirameki\Core\Config;
use Kirameki\Redis\Exceptions\CommandException;
use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Redis\Exceptions\RedisException;
use Kirameki\Support\Arr;
use Redis;
use RedisCluster;
use RedisException as PhpRedisException;
use function strlen;
use function substr;

abstract class Adapter
{
    /**
     * @var Redis|RedisCluster|null
     */
    protected ?object $redis;

    /**
     * @param Config $config
     */
    public function __construct(protected Config $config)
    {
        $this->redis = null;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->config->getStringOr('prefix', '');
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): static
    {
        $this->config->set('prefix', $prefix);
        $this->redis?->setOption(Redis::OPT_PREFIX, $prefix);
        return $this;
    }

    /**
     * @return bool
     */
    public function connect(): bool
    {
        $this->getConnectedClient();
        return true;
    }

    /**
     * @return bool
     */
    public function disconnect(): bool
    {
        return (bool) $this->redis?->close();
    }

    /**
     * @return $this
     */
    public function reconnect(): static
    {
        $this->disconnect();
        $this->connect();
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->redis !== null;
    }

    /**
     * @return Redis|RedisCluster
     */
    abstract protected function getConnectedClient(): object;

    /**
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param bool $persistent
     * @return Redis
     */
    protected function connectDirect(string $host, int $port, float $timeout, bool $persistent): Redis
    {
        $redis = new Redis();
        $config = $this->config;

        try {
            $persistent
                ? $redis->pconnect($host, $port, $timeout)
                : $redis->connect($host, $port, $timeout);

            $prefix = $config->getStringOr('prefix', default: '');
            $username = $config->getStringOrNull('username');
            $password = $config->getStringOrNull('password');
            $database = $config->getIntOrNull('database');

            $redis->setOption(Redis::OPT_PREFIX, $prefix);
            $redis->setOption(Redis::OPT_TCP_KEEPALIVE, true);
            $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_PREFIX);
            $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

            if ($username !== null && $password !== null) {
                $redis->auth(Arr::compact(['user' => $username, 'pass' => $password]));
            }

            if ($database !== null) {
                $redis->select($database);
            }
        }
        catch (PhpRedisException $e) {
            $this->throwAs(ConnectionException::class, $e);
        }

        return $redis;
    }

    /**
     * @return list<Redis>
     */
    abstract public function connectToNodes(): array;

    /**
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    public function command(string $name, mixed ...$args): mixed
    {
        $redis = $this->redis ?? $this->getConnectedClient();

        try {
            $result = $redis->$name(...$args);
        }
        catch (PhpRedisException $e) {
            $this->throwAs(CommandException::class, $e);
        }

        if ($err = $redis->getLastError()) {
            $redis->clearLastError();
            throw new CommandException($err);
        }

        return $result;
    }

    /**
     * @param class-string<RedisException> $class
     * @param PhpRedisException $base
     * @return no-return
     */
    protected function throwAs(string $class, PhpRedisException $base): never
    {
        // Dig through exceptions to get to the root one that is not wrapped in RedisException
        // since wrapping it twice is pointless.
        $root = $base;
        while ($last = $root->getPrevious()) {
            $root = $last;
        }
        throw new $class($base->getMessage(), $base->getCode(), $root);
    }

    /**
     * @param string|null $pattern
     * @param int $count
     * @param bool $prefixed
     * @return Generator<int, string>
     */
    public function scan(string $pattern = null, int $count = 0, bool $prefixed = false): Generator
    {
        $prefix = $this->getPrefix();

        // If the prefix is defined, doing an empty scan will actually call scan with `"MATCH" "{prefix}"`
        // which does not return the expected result. To get the expected result, '*' needs to be appended.
        if ($pattern === null && $prefix !== '') {
            $pattern = '*';
        }

        // PhpRedis returns the results WITH the prefix, so we must trim it after retrieval if `$prefixed` is
        // set to `false`. The prefix length is necessary for the `substr` used later inside the loop.
        $removablePrefixLength = strlen($prefixed ? '' : $prefix);

        foreach ($this->connectToNodes() as $node) {
            $cursor = null;
            do {
                $keys = $node->scan($cursor, $pattern, $count);
                if ($keys !== false) {
                    foreach ($keys as $key) {
                        if ($removablePrefixLength > 0) {
                            $key = substr($key, $removablePrefixLength);
                        }
                        yield $key;
                    }
                }
            }
            while($cursor > 0);
        }
    }
}
