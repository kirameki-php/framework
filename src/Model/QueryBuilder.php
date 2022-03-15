<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Query\Builders\ConditionBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Model\Paginators\BasicPaginator;
use Kirameki\Model\Paginators\CursorPaginator;
use Kirameki\Model\Paginators\OffsetPaginator;
use Kirameki\Support\Arr;
use RuntimeException;

/**
 * @template T of Model
 */
class QueryBuilder extends SelectBuilder
{
    /**
     * @var Reflection<T>
     */
    protected Reflection $reflection;

    /**
     * @param DatabaseManager $db
     * @param Reflection<T> $reflection
     */
    public function __construct(DatabaseManager $db, Reflection $reflection)
    {
        $connection = $db->using($reflection->connectionName);
        parent::__construct($connection);
        $this->reflection = $reflection;

        $this->from($reflection->tableName);
    }

    /**
     * @return ModelCollection<int, T>
     */
    public function all(): ModelCollection
    {
        $reflection = $this->reflection;
        /** @var array<int, array<string, mixed>> $results */
        $results = $this->execute();
        $models = Arr::map($results, static fn(array $props) => $reflection->makeModel($props, true));
        return new ModelCollection($reflection, $models);
    }

    /**
     * @return T|null
     */
    public function first(): ?Model
    {
        /** @var array<int, array<string, mixed>> $results */
        $results = $this->copy()->limit(1)->execute();
        return isset($results[0])
            ? $this->reflection->makeModel($results[0], true)
            : null;
    }

    /**
     * @return T
     */
    public function firstOrFail(): Model
    {
        return $this->first() ?? throw new RuntimeException('No record found for query: '.$this->toSql());
    }

    /**
     * @param int $per
     * @param int $page
     * @return OffsetPaginator<T>
     */
    public function offsetPaginate(int $per, int $page): OffsetPaginator
    {
        $models = $this->offset($per * $page)->limit($per)->all();
        $totalRows = (int) $this->count();
        return new OffsetPaginator($models, $totalRows, $per, $page);
    }

    /**
     * @param int $per
     * @param string|null $cursor
     * @return ModelCollection<int, T>
     */
    public function cursorPaginate(int $per, ?string $cursor): ModelCollection
    {
        $orderBy = $this->statement->orderBy ?? [];

        if (empty($orderBy)) {
            throw new RuntimeException("Cursor pagination requires at least one column in ORDER BY");
        }

        if ($cursor !== null) {
            $column = array_key_first($orderBy);
            $operator = strtolower($orderBy[$column]) === 'asc' ? '<' : '>';
            $this->where($column, $operator, $cursor);
        }

        $models = $this->limit($per + 1)->all();

        $nextCursor = $models->pop();

        return new CursorPaginator($models);
    }

    /**
     * @param string|ConditionBuilder $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where(ConditionBuilder|string $column, mixed $operator = null, mixed $value = null): static
    {
        $num = func_num_args();

        if (is_string($column)) {
            if ($num === 2 && $operator instanceof Model) {
                $dstModel = $operator;
            }
            elseif ($num === 3 && $value instanceof Model && ($operator === null || $operator === '=')) {
                $dstModel = $value;
            }

            if (isset($dstModel) && $relation = $this->reflection->relations[$column] ?? false) {
                foreach ($relation->getKeyPairs() as $srcKeyName => $dstKeyName) {
                    parent::where($srcKeyName, $dstModel->getProperty($dstKeyName));
                }
                return $this;
            }
        }

        return parent::where($column, $operator, $value);
    }
}
