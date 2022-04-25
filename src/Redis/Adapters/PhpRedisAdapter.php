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

        $host = $config->getStringOrNull('host') ?? 'localhost';
        $port = $config->getIntOrNull('port') ?? 6379;
        $timeout = $config->getFloatOrNull('timeout') ?? 0.0;
        $prefix = $config->getStringOrNull('prefix') ?? '';

        $config->getBoolOrNull('persistent')
            ? $this->phpRedis->pconnect($host, $port, $timeout)
            : $this->phpRedis->connect($host, $port, $timeout);

        $this->phpRedis->setOption(Redis::OPT_PREFIX, $prefix);
        $this->phpRedis->setOption(Redis::OPT_TCP_KEEPALIVE, true);
        $this->phpRedis->setOption(Redis::SCAN_NORETRY, true);
        $this->phpRedis->setOption(Redis::SCAN_PREFIX, true);
        $this->phpRedis->setOption(Redis::SERIALIZER_IGBINARY, true);

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
     * @param array<mixed> $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->command($name, $args);
    }

    /**
     * @inheritDoc
     */
    public function command(string $name, array $args): mixed
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
     * @param string $key
     * @param mixed $value
     * @param SetOptions|null $options
     * @return mixed
     */
    public function set(string $key, mixed $value, SetOptions $options = null): mixed
    {
        $opts = $options?->toArray() ?? [];
        return $this->command('set', [$key, $value, ...$opts]);
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
