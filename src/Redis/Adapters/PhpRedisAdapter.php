<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Kirameki\Core\Config;
use Kirameki\Redis\Exceptions\CommandException;
use Kirameki\Redis\Support\SetOptions;
use Kirameki\Redis\Support\Type;
use LogicException;
use Redis;
use RedisException;

class PhpRedisAdapter implements Adapter
{
    /**
     * @var Redis
     */
    protected Redis $phpRedis;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->phpRedis = new Redis();
        $this->config = $config;
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
        return $this->config->getStringOrNull('prefix') ?? '';
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): static
    {
        $this->config->set('prefix', $prefix);

        if ($this->isConnected()) {
            $this->phpRedis->setOption(Redis::OPT_PREFIX, $prefix);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function connect(): static
    {
        $config = $this->config;
        $redis = $this->phpRedis;

        $host = $config->getStringOrNull('host') ?? 'localhost';
        $port = $config->getIntOrNull('port') ?? 6379;
        $timeout = $config->getFloatOrNull('timeout') ?? 0.0;
        $prefix = $config->getStringOrNull('prefix') ?? '';
        $password = $config->getStringOrNull('password');

        $config->getBoolOrNull('persistent')
            ? $redis->pconnect($host, $port, $timeout)
            : $redis->connect($host, $port, $timeout);

        $redis->setOption(Redis::OPT_PREFIX, $prefix);
        $redis->setOption(Redis::OPT_TCP_KEEPALIVE, true);
        $redis->setOption(Redis::SCAN_NORETRY, true);
        $redis->setOption(Redis::SCAN_PREFIX, true);
        $redis->setOption(Redis::SERIALIZER_IGBINARY, true);

        if ($password !== null && $password !== '') {
            $redis->auth($password);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        return $this->phpRedis->close();
    }

    /**
     * @inheritDoc
     */
    public function reconnect(): static
    {
        $this->disconnect();
        return $this->connect();
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->phpRedis->isConnected();
    }

    /**
     * @param string $name
     * @param mixed $args
     * @return mixed
     */
    public function __call(string $name, mixed ...$args): mixed
    {
        return $this->command($name, ...$args);
    }

    /**
     * @inheritDoc
     */
    public function command(string $name, mixed ...$args): mixed
    {
        $instance = $this->phpRedis;

        try {
            $result = ($instance->$name)(...$args);
        } catch (RedisException $e) {
            throw new CommandException($e->getMessage(), $e->getCode(), $e);
        }

        if ($err = $instance->getLastError()) {
            $instance->clearLastError();
            throw new CommandException($err);
        }

        return $result;
    }

    /**
     * @param string $message
     * @return string
     */
    public function echo(string $message): string
    {
        return $this->command('echo', $message);
    }

    /**
     * @param string ...$key
     * @return int
     */
    public function exists(string ...$key): int
    {
        return (int)$this->command(...$key);
    }

    /**
     * @return bool
     */
    public function ping(): bool
    {
        return $this->command('ping');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param SetOptions|null $options
     * @return mixed
     */
    public function set(string $key, mixed $value, SetOptions $options = null): mixed
    {
        $opts = $options?->toArray() ?? [];
        return $this->command('set', $key, $value, ...$opts);
    }

    /**
     * @return float
     */
    public function time(): float
    {
        /** @var list<int> $time */
        $time = $this->command('time');
        return (float)"$time[0].$time[1]";
    }

    /**
     * @param string $key
     * @return Type
     */
    public function type(string $key): Type
    {
        $type = $this->command('type', [$key]);
        return match ($type) {
            Redis::REDIS_NOT_FOUND => Type::None,
            Redis::REDIS_STRING => Type::String,
            Redis::REDIS_LIST => Type::List,
            Redis::REDIS_SET => Type::Set,
            Redis::REDIS_ZSET => Type::ZSet,
            Redis::REDIS_HASH => Type::Hash,
            Redis::REDIS_STREAM => Type::Stream,
            default => throw new LogicException("Unknown Type: $type"),
        };
    }
}
