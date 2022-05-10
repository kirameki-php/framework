<?php declare(strict_types=1);

namespace Kirameki\Redis\Adapters;

use Closure;
use Kirameki\Core\Config;
use Kirameki\Redis\Exceptions\CommandException;
use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Redis\Support\ScanResult;
use Kirameki\Redis\Support\SetOptions;
use Kirameki\Redis\Support\Type;
use LogicException;
use Redis;
use RedisException;
use Throwable;
use function dump;

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
        $database = $config->getIntOrNull('database');

        try {
            $config->getBoolOrNull('persistent')
                ? $redis->pconnect($host, $port, $timeout)
                : $redis->connect($host, $port, $timeout);
        } catch (RedisException $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $this->getRootException($e));
        }

        $redis->setOption(Redis::OPT_PREFIX, $prefix);
        $redis->setOption(Redis::OPT_TCP_KEEPALIVE, true);
        $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_PREFIX);
        $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

        if ($password !== null && $password !== '') {
            $redis->auth($password);
        }

        if ($database !== null) {
            $redis->select($database);
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
     * @param array<mixed> $args
     * @return mixed
     */
    public function __call(string $name, array $args)
    {
        return $this->command($name, ...$args);
    }

    /**
     * @inheritDoc
     */
    public function command(string $name, mixed ...$args): mixed
    {
        return $this->process(static function(Redis $client) use ($name, $args): mixed {
            return $client->$name(...$args);
        });
    }

    /**
     * @param Closure(Redis): mixed $callback
     * @return mixed
     */
    protected function process(Closure $callback): mixed
    {
        $client = $this->getClient();

        try {
            $result = $callback($client);
        } catch (RedisException $e) {
            // Dig through exceptions to get to the root one that is not wrapped in RedisException
            // since wrapping it twice is pointless.
            throw new CommandException($e->getMessage(), $e->getCode(), $this->getRootException($e));
        }

        if ($err = $client->getLastError()) {
            $client->clearLastError();
            throw new CommandException($err);
        }

        return $result;
    }

    /**
     * @return Redis
     */
    protected function getClient(): Redis
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->phpRedis;
    }

    /**
     * Dig through exceptions to get to the root one that is not wrapped in RedisException
     * since wrapping it twice is pointless.
     *
     * @param Throwable $throwable
     * @return Throwable
     */
    protected function getRootException(Throwable $throwable): Throwable
    {
        $root = $throwable;
        while ($last = $root->getPrevious()) {
            $root = $last;
        }
        return $root;
    }

    /**
     * @param string $pattern
     * @param int $count
     * @return ScanResult
     */
    public function scan(?string $pattern = null, ?int $count = null): ScanResult
    {
        return $this->process(static function (Redis $client) use ($pattern, $count): ScanResult {
            return new ScanResult(static function() use ($client, $pattern, $count) {
                $iterator = null;
                $index = 0;
                $count ??= 10_000;
                while(true) {
                    $keys = $client->scan($iterator, $pattern, $count);
                    if ($keys === false) {
                        break;
                    }
                    foreach ($keys as $key) {
                        yield $index => $key;
                        ++$index;
                    }
                }
            });
        });
    }

    /**
     * @param int $index
     * @return bool
     */
    public function select(int $index): bool
    {
        return $this->command('select', $index);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param SetOptions|null $options
     * @return mixed
     */
    public function set(string $key, mixed $value, ?SetOptions $options = null): mixed
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
