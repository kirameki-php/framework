<?php declare(strict_types=1);

namespace Kirameki\Redis;

use Kirameki\Event\EventManager;
use Kirameki\Redis\Adapters\Adapter;
use Kirameki\Redis\Events\CommandExecuted;
use Kirameki\Redis\Support\SetOptions;
use Kirameki\Redis\Support\Type;
use function hrtime;

/**
 *
 * @method string echo(string $message)
 * @method int exists(string ...$key)
 * @method bool expire(string $key, int $time)
 * @method bool expireAt(string $key, int $time)
 * @method int expireTime()
 * @method bool ping()
 * @method mixed set(string $key, mixed $value, SetOptions $options = null)
 * @method array|false scan(int &$iterator, ?string $pattern = null, int $count = 0)
 * @method float time()
 * @method Type type(string $key)
 *
 * KEYS ----------------------------------------------------------------------------------------------------------------
 * @method int del(string ...$key)
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
 * @method array<mixed> mget(string ...$keys)
 * @method mixed getSet(string $key, $value)
 * @method int incr(string $key)
 * @method int incrBy(string $key, int $amount)
 * @method float incrByFloat(string $key, float $amount)
 */
class Connection
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Adapter
     */
    protected Adapter $adapter;

    /**
     * @var EventManager
     */
    protected EventManager $event;

    /**
     * @param string $name
     * @param Adapter $adapter
     * @param EventManager $event
     */
    public function __construct(string $name, Adapter $adapter, EventManager $event)
    {
        $this->name = $name;
        $this->adapter = $adapter;
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
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
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    protected function command(string $name, mixed ...$args): mixed
    {
        $then = hrtime(true);

        $result = ($this->adapter->$name)(...$args);

        $timeMs = (hrtime(true) - $then) * 1_000_000;

        $this->event->dispatchClass(CommandExecuted::class, $this, $name, $args, $result, $timeMs);

        return $result;
    }
}
