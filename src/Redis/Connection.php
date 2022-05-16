<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Closure;
use Generator;
use Kirameki\Core\Config;
use Kirameki\Event\EventManager;
use Kirameki\Redis\Events\CommandExecuted;
use Kirameki\Redis\Exceptions\CommandException;
use Kirameki\Redis\Exceptions\ConnectionException;
use Kirameki\Redis\Exceptions\RedisException;
use Kirameki\Redis\Support\SetOptions;
use Kirameki\Redis\Support\Type;
use Kirameki\Support\ItemIterator;
use Kirameki\Support\Str;
use LogicException;
use Redis;
use RedisException as PhpRedisException;
use Throwable;
use Traversable;
use Webmozart\Assert\Assert;
use function count;
use function explode;
use function func_get_args;
use function hrtime;
use function is_float;
use function iterator_to_array;
use function strlen;
use function substr;

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
 * @method mixed  brPop(string[] $key, int $timeout)
 * @method mixed  brpoplpush(string $source, string $destination, int $timeout)
 * @method mixed  lLen($key)
 * @method mixed  lPop(string $key)
 * @method mixed  lPushx(string $key, $value)
 * @method mixed  lRange(string $key, int $start, int $end)
 * @method mixed  lRem(string $key, $value, int $count)
 * @method mixed  lSet(string $key, int $index, $value)
 * @method mixed  lTrim(string $key, int $start, int $end)
 * @method mixed  rPop(string $key)
 * @method mixed  rpoplpush(string $source, string $destination)
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
 * UNSUPPORTED COMMANDS
 * - APPEND: does not work well with serialization
 * - BLPOP: waiting for PhpRedis to implement it
 * - BLMPOP: waiting for PhpRedis to implement it
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
     * @param string $command
     * @param mixed ...$args
     * @return mixed
     */
    public function run(string $command, mixed ...$args): mixed
    {
        return $this->process($command, $args, static function(Redis $client, string $command, array $args): mixed {
            return $client->$command(...$args);
        });
    }

    /**
     * @param string $command
     * @param array<mixed> $args
     * @param Closure $callback
     * @return mixed
     */
    protected function process(string $command, array $args, Closure $callback): mixed
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $phpRedis = $this->phpRedis;

        $then = hrtime(true);

        try {
            $result = $callback($phpRedis, $command, $args);
        } catch (PhpRedisException $e) {
            $this->throwAs(CommandException::class, $e);
        }

        if ($err = $phpRedis->getLastError()) {
            $phpRedis->clearLastError();
            throw new CommandException($err);
        }

        $timeMs = (hrtime(true) - $then) * 1_000_000;

        $this->event->dispatchClass(CommandExecuted::class, $this, $command, $args, $result, $timeMs);

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

    # region CONNECTION ------------------------------------------------------------------------------------------------

    /**
     * @link https://redis.io/commands/client-list
     *
     * @return list<string>
     */
    public function clientList(): array
    {
        return $this->run('client', 'list');
    }

    /**
     * @link https://redis.io/commands/client-info
     *
     * @return array<string, ?scalar>
     */
    public function clientInfo(): array
    {
        $result = $this->run('client', 'info');
        $formatted = [];
        foreach (explode(' ', $result) as $item) {
            [$key, $val] = explode('=', $item);
            $formatted[$key] = Str::infer($val);
        }
        return $formatted;
    }

    /**
     * @link https://redis.io/commands/echo
     *
     * @param string $message
     * @return string
     */
    public function echo(string $message): string
    {
        return $this->run('echo', $message);
    }

    /**
     * @link https://redis.io/commands/ping
     * @return bool
     */
    public function ping(): bool
    {
        return $this->run('ping');
    }

    /**
     * @link https://redis.io/commands/select
     *
     * @param int $index
     * @return bool
     */
    public function select(int $index): bool
    {
        return $this->run('select', $index);
    }

    # endregion CONNECTION ---------------------------------------------------------------------------------------------

    # region SERVER ----------------------------------------------------------------------------------------------------

    /**
     * @link https://redis.io/commands/dbsize
     *
     * @return int
     */
    public function dbSize(): int
    {
        return $this->run('dbSize');
    }

    /**
     * @param int $per
     * @return int
     */
    public function flushKeys(int $per = 100_000): int
    {
        $keys = $this->scan(null, $per)->toArray();
        return count($keys) > 0
            ? $this->del(...$keys)
            : 0;
    }

    /**
     * @link https://redis.io/commands/time
     *
     * @return float
     */
    public function time(): float
    {
        /** @var list<int> $time */
        $time = $this->run('time');
        return (float)"$time[0].$time[1]";
    }

    # endregion SERVER -------------------------------------------------------------------------------------------------

    # region STRING ----------------------------------------------------------------------------------------------------

    /**
     * @param string $key
     * @param int $by
     * @return int  the decremented value
     */
    public function decr(string $key, int $by = 1): int
    {
        return $by === 1
            ? $this->run('decr', $key)
            : $this->run('decrBy', $key, $by);
    }

    /**
     * @param string $key
     * @param float $by
     * @return float  the decremented value
     */
    public function decrByFloat(string $key, float $by): float
    {
        return $this->run('incrByFloat', $key, -$by);
    }

    /**
     * @link https://redis.io/commands/get
     *
     * @param string $key
     * @return mixed|false  `false` if key does not exist.
     */
    public function get(string $key): mixed
    {
        return $this->run('get', $key);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int  the incremented value
     */
    public function incr(string $key, int $by = 1): int
    {
        return $by === 1
            ? $this->run('incr', $key)
            : $this->run('incrBy', $key, $by);
    }

    /**
     * @param string $key
     * @param float $by
     * @return float  the incremented value
     */
    public function incrByFloat(string $key, float $by): float
    {
        return $this->run('incrByFloat', $key, $by);
    }

    /**
     * @link https://redis.io/commands/mget
     *
     * @param string ...$key
     * @return array<string, mixed|false>  Returns `[{retrieved_key} => value, ...]`. `false` if key is not found.
     */
    public function mGet(string ...$key): array
    {
        Assert::isNonEmptyList($key);
        $values = $this->run('mGet', $key);
        $result = [];
        $index = 0;
        foreach ($key as $k) {
            $result[$k] = $values[$index];
            ++$index;
        }
        return $result;
    }

    /**
     * @link https://redis.io/commands/mset
     *
     * @param iterable<string, mixed> $pairs
     * @return bool
     */
    public function mSet(iterable $pairs): bool
    {
        Assert::isNonEmptyMap($pairs);
        return $this->run('mSet', $pairs);
    }

    /**
     * @link https://redis.io/commands/set
     *
     * @param string $key
     * @param mixed $value
     * @param SetOptions|null $options
     * @return mixed
     */
    public function set(string $key, mixed $value, ?SetOptions $options = null): mixed
    {
        $opts = $options?->toArray() ?? [];
        return $this->run('set', $key, $value, ...$opts);
    }

    # endregion STRING -------------------------------------------------------------------------------------------------

    # region KEY -------------------------------------------------------------------------------------------------------

    /**
     * @see https://redis.io/commands/del
     *
     * @param string ...$key
     * @return int Returns the number of keys that were removed.
     */
    public function del(string ...$key): int
    {
        Assert::isNonEmptyList($key);
        return $this->run('del', ...$key);
    }

    /**
     * @see https://redis.io/commands/exists
     *
     * @param string ...$key
     * @return int
     */
    public function exists(string ...$key): int
    {
        Assert::isNonEmptyList($key);
        return $this->run('exists', ...$key);
    }

    /**
     * @link https://redis.io/commands/type
     *
     * @param string $key
     * @return Type
     */
    public function type(string $key): Type
    {
        $type = $this->run('type', $key);
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

    /**
     *
     * Will iterate through the set of keys that match `$pattern` or all keys if no pattern is given.
     * Scan has the following limitations
     * - A given element may be returned multiple times.
     * -
     * @see https://redis.io/commands/scan
     *
     * @param string|null $pattern  Patterns to be scanned. Add '*' as suffix to match string. Returns all keys if `null`.
     * @param int $count  Number of elements returned per iteration. This is just a hint and is not guaranteed.
     * @param bool $prefixed  If set to `true`, result will contain the prefix set in the config. (default: `false`)
     * @return ItemIterator<int, string>
     */
    public function scan(?string $pattern = null, int $count = 10_000, bool $prefixed = false): ItemIterator
    {
        $args = func_get_args();
        $generatorCall = static function (Redis $client) use ($pattern, $count, $prefixed): Generator {
            $prefix = $client->_prefix('');

            // If the prefix is defined, doing an empty scan will actually call scan with `"MATCH" "{prefix}"`
            // which does not return the expected result. To get the expected result, '*' needs to be appended.
            if ($pattern === null && $prefix !== '') {
                $pattern = '*';
            }

            // PhpRedis returns the results WITH the prefix, so we must trim it after retrieval if `$prefixed` is
            // set to `false`. The prefix length is necessary for the `substr` used later inside the loop.
            $removablePrefixLength = strlen($prefixed ? '' : $prefix);

            $cursor = null;
            do {
                $keys = $client->scan($cursor, $pattern, $count);
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
        };

        return $this->process('scan', $args, fn () => new ItemIterator($generatorCall($this->phpRedis)));
    }

    # endregion KEY ----------------------------------------------------------------------------------------------------

    # region LIST ------------------------------------------------------------------------------------------------------

    /**
     * @see https://redis.io/commands/blpop
     *
     * @param iterable<string> $keys
     * @param int $timeout  If no timeout is set, it will be set to 0 which is infinity.
     * @return array<string, mixed>|null  Returns null on timeout
     */
    public function blPop(iterable $keys, int $timeout = 0): ?array
    {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys);
        }

        /** @var array{ 0?: string, 1?: mixed } $result */
        $result = $this->run('blPop', $keys, $timeout);

        return (count($result) > 0)
            ? [$result[0] => $result[1]]
            : null;
    }

    /**
     * @see https://redis.io/commands/lindex
     *
     * @param string $key
     * @param int $index  Zero based. Use negative indices to designate elements starting at the tail of the list.
     * @return mixed|false  The value at index or `false` if... (1) key is missing or (2) index is missing.
     * @throws CommandException  if key set but is not a list.
     */
    public function lIndex(string $key, int $index): mixed
    {
        return $this->run('lIndex', $key, $index);
    }

    /**
     * Each element is inserted to the head of the list, from the leftmost to the rightmost element.
     * Ex: `$client->lPush('mylist', 'a', 'b', 'c')` will create a list `["c", "b", "a"]`
     * @see https://redis.io/commands/lpush
     *
     * @param string $key
     * @param mixed ...$value
     * @return int  length of the list after the push operation.
     */
    public function lPush(string $key, mixed ...$value): int
    {
        return $this->run('lPush', $key, ...$value);
    }

    /**
     * Each element is inserted to the tail of the list, from the leftmost to the rightmost element.
     * Ex: `$client->rPush('mylist', 'a', 'b', 'c')` will create a list `["a", "b", "c"]`.
     * @see https://redis.io/commands/rpush
     *
     * @param string $key
     * @param mixed ...$value
     * @return int  length of the list after the push operation.
     */
    public function rPush(string $key, mixed ...$value): int
    {
        return $this->run('rPush', $key, ...$value);
    }

    # endregion LIST ---------------------------------------------------------------------------------------------------
}
