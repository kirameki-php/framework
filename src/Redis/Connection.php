<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Closure;
use Iterator;
use Kirameki\Core\Config;
use Kirameki\Event\EventManager;
use Kirameki\Redis\Events\CommandExecuted;
use Kirameki\Redis\Exceptions\CommandException;
use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Redis\Exceptions\RedisException;
use Kirameki\Redis\Support\SetOptions;
use Kirameki\Redis\Support\Type;
use Kirameki\Support\Str;
use LogicException;
use Redis;
use RedisException as PhpRedisException;
use Throwable;
use Webmozart\Assert\Assert;
use function count;
use function dump;
use function explode;
use function func_get_args;
use function hrtime;
use function iterator_to_array;

/**
 * @method bool expire(string $key, int $time)
 * @method bool expireAt(string $key, int $time)
 * @method int expireTime()
 *
 * KEYS ----------------------------------------------------------------------------------------------------------------
 * @method array<int, string> keys(string $pattern)
 * @method bool move(string $key, int $db)
 * @method bool persist(string $key)
 * @method bool pExpire(string $key, int $time)
 * @method bool pExpireAt(string $key, int $time)
 * @method int pExpireTime()
 * @method int pTtl()
 *
 * @method bool|int ttl(string $key)
 *
 * HASHES --------------------------------------------------------------------------------------------------------------
 * @method mixed hDel(string $key, string $field)
 * @method bool hExists(string $key, string $field)
 * @method mixed hGet(string $key, string $field)
 * @method array hGetAll(string $key)
 * @method mixed hIncrBy(string $key, string $field, int $amount)
 * @method array hKeys(string $key)
 * @method int hLen(string $key)
 * @method mixed hSet(string $key, string $field, $value)
 * @method mixed hSetNx(string $key, string $field, $value)
 * @method array hVals(string $key)
 *
 * LISTS ---------------------------------------------------------------------------------------------------------------
 * @method mixed  blPop(string[] $key, int $timeout)
 * @method mixed  brPop(string[] $key, int $timeout)
 * @method mixed  brpoplpush(string $source, string $destination, int $timeout)
 * @method mixed  lIndex(string $key, int $index)
 * @method mixed  lLen($key)
 * @method mixed  lPop(string $key)
 * @method mixed  lPush(string $key, $value)
 * @method mixed  lPushx(string $key, $value)
 * @method mixed  lRange(string $key, int $start, int $end)
 * @method mixed  lRem(string $key, $value, int $count)
 * @method mixed  lSet(string $key, int $index, $value)
 * @method mixed  lTrim(string $key, int $start, int $end)
 * @method mixed  rPop(string $key)
 * @method mixed  rpoplpush(string $source, string $destination)
 * @method mixed  rPush(string $key, $value)
 * @method mixed  rPushx(string $key, $value)
 *
 * SORTED SETS ---------------------------------------------------------------------------------------------------------
 * @method array bzPopMax(string|array $key, int $timeout) // A timeout of zero can be used to block indefinitely
 * @method array bzPopMin(string|array $key, int $timeout) // A timeout of zero can be used to block indefinitely
 * @method int zAdd(string $key, array $options, float $score, string $member, ...$scoreThenMember) // options: ['NX', 'XX', 'CH', 'INCR']
 * @method int zCard(string $key)
 * @method int zCount(string $key, string $start, string $end)
 * @method int zIncrBy(string $key, float $increment, string $member)
 * @method int zInterStore(string $output, $zSetKeys, array $weight = null, string $aggregateFunction = 'SUM')
 * @method int zLexCount(string $key, int $min, int $max)
 * @method array zPopMax(string $key, int $count = 1)
 * @method array zPopMin(string $key, int $count = 1)
 * @method array zRange(string $key, int $start, int $end, bool|null $withScores = null)
 * @method array|bool zRangeByLex(string $key, int $min, int $max, int $offset = null, int $limit = null)
 * @method array|bool zRangeByScore(string $key, int $start, int $end, array $options = [])  // options: { withscores => bool, limit => [$offset, $count] }
 * @method bool|int zRank(string $key, string $member)
 * @method bool|int zRem(string $key, string ...$members)
 * @method bool|int zRemRangeByRank(string $key, int $start, int $end)
 * @method bool|int zRemRangeByScore(string $key, float|string $start, float|string $end)
 * @method array zRevRange(string $key, int $start, int $end, bool|null $withScores = null)
 * @method array zRevRangeByLex(string $key, int $min, int $max, int $offset = null, int $limit = null)
 * @method array|bool zRevRangeByScore(string $key, int $start, int $end, array $options = [])  // options: { withscores => bool, limit => [$offset, $count] }
 * @method bool|int zRevRank(string $key, string $member)
 * @method bool|int zScore(string $key, string $member)
 * @method bool|int zUnionStore(string $output, array $zSetKeys, array $weights = null, string $aggregateFunction = 'SUM')
 *
 * STRING --------------------------------------------------------------------------------------------------------------
 * @method int decr(string $key)
 * @method int decrBy(string $key, int $amount)
 * @method mixed get(string $key)
 * @method mixed getSet(string $key, $value)
 * @method int incr(string $key)
 * @method int incrBy(string $key, int $amount)
 * @method float incrByFloat(string $key, float $amount)
 */
class Connection
{
    /**
     * @var Redis
     */
    protected Redis $phpRedis;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var EventManager
     */
    protected EventManager $event;

    /**
     * @param string $name
     * @param Config $config
     * @param EventManager $event
     */
    public function __construct(string $name, Config $config, EventManager $event)
    {
        $this->phpRedis = new Redis();
        $this->name = $name;
        $this->config = $config;
        $this->event = $event;
    }

    /**
     * @return Redis
     */
    public function getClient(): Redis
    {
        return $this->phpRedis;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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

        if ($this->isConnected()) {
            $this->phpRedis->setOption(Redis::OPT_PREFIX, $prefix);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function connect(): static
    {
        $config = $this->config;
        $redis = $this->phpRedis;

        $host = $config->getStringOr('host', default: 'localhost');
        $port = $config->getIntOr('port', default: 6379);
        $timeout = $config->getFloatOr('timeout', default: 0.0);
        $prefix = $config->getStringOr('prefix', default: '');
        $password = $config->getStringOrNull('password');
        $database = $config->getIntOrNull('database');

        try {
            $config->getBoolOr('persistent', default: false)
                ? $redis->pconnect($host, $port, $timeout)
                : $redis->connect($host, $port, $timeout);
        } catch (PhpRedisException $e) {
            $this->throwAs(ConnectionException::class, $e);
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
     * @return bool
     */
    public function disconnect(): bool
    {
        return $this->phpRedis->close();
    }

    /**
     * @return $this
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
     * @param mixed ...$args
     * @return mixed
     */
    protected function command(string $name, mixed ...$args): mixed
    {
        return $this->process($name, $args, function(string $name, array $args): mixed {
            return $this->phpRedis->$name(...$args);
        });
    }

    /**
     * @param string $name
     * @param array<mixed> $args
     * @param Closure $callback
     * @return mixed
     */
    protected function process(string $name, array $args, Closure $callback): mixed
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $then = hrtime(true);

        try {
            $result = $callback($name, $args);
        } catch (PhpRedisException $e) {
            $this->throwAs(CommandException::class, $e);
        }

        if ($err = $this->phpRedis->getLastError()) {
            $this->phpRedis->clearLastError();
            throw new CommandException($err);
        }

        $timeMs = (hrtime(true) - $then) * 1_000_000;

        $this->event->dispatchClass(CommandExecuted::class, $this, $name, $args, $result, $timeMs);

        return $result;
    }

    /**
     * @param class-string<RedisException> $exceptionClass
     * @param Throwable $base
     * @return no-return
     */
    protected function throwAs(string $exceptionClass, Throwable $base): never
    {
        // Dig through exceptions to get to the root one that is not wrapped in RedisException
        // since wrapping it twice is pointless.
        $root = $base;
        while ($last = $root->getPrevious()) {
            $root = $last;
        }
        throw new $exceptionClass($base->getMessage(), $base->getCode(), $root);
    }

    /**
     * @param int $per
     * @return int
     */
    public function flush(int $per = 100_000): int
    {
        $keys = iterator_to_array($this->scan(null, $per));
        return count($keys) > 0
            ? $this->del(...$keys)
            : 0;
    }

    /**
     * @return list<string>
     */
    public function clientList(): array
    {
        return $this->command('client', 'list');
    }

    /**
     * @return array<string, ?scalar>
     */
    public function clientInfo(): array
    {
        $result = $this->command('client', 'info');
        $formatted = [];
        foreach (explode(' ', $result) as $item) {
            [$key, $val] = explode('=', $item);
            $formatted[$key] = Str::infer($val);
        }
        return $formatted;
    }

    /**
     * @param string ...$key
     * @return int
     */
    public function del(string ...$key): int
    {
        Assert::isNonEmptyList($key);
        return $this->command('del', ...$key);
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
        Assert::isNonEmptyList($key);
        return $this->command('exists', ...$key);
    }

    /**
     * Returns array of retrieved key => value.
     * if key is not found, the value will be set to false.
     *
     * @param string ...$key
     * @return array<string, mixed|false>
     */
    public function mGet(string ...$key): array
    {
        Assert::isNonEmptyList($key);
        $values = $this->command('mGet', $key);
        $result = [];
        foreach ($key as $i => $k) {
            $result[$k] = $values[$i];
        }
        return $result;
    }

    /**
     * @param iterable<string, mixed> $pairs
     * @return bool
     */
    public function mSet(iterable $pairs): bool
    {
        Assert::isNonEmptyMap($pairs);
        return $this->command('mSet', $pairs);
    }

    /**
     * @return bool
     */
    public function ping(): bool
    {
        return $this->command('ping');
    }

    /**
     * @param string $pattern
     * @param int $count
     * @return Iterator<int, string>
     */
    public function scan(?string $pattern = null, ?int $count = null): Iterator
    {
        return $this->process('scan', func_get_args(), function () use ($pattern, $count): Iterator {
            $iterator = null;
            $index = 0;
            $count ??= 10_000;
            $client = $this->phpRedis;
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
        $type = $this->command('type', $key);
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
