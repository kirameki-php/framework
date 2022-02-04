<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Query\Builders\ConditionBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
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
        $connection = $db->using($reflection->connection);
        parent::__construct($connection);
        $this->reflection = $reflection;

        $this->from($reflection->table);
    }

    /**
     * @return ModelCollection<int, T>
     */
    public function all(): ModelCollection
    {
        $reflection = $this->reflection;
        /** @var array<int, array<string, mixed>> $results */
        $results = $this->execSelect();
        $models = Arr::map($results, static fn(array $props) => $reflection->makeModel($props, true));
        return new ModelCollection($reflection, $models);
    }

    /**
     * @return T|null
     */
    public function first(): ?Model
    {
        /** @var array<int, array<string, mixed>> $results */
        $results = $this->copy()->limit(1)->execSelect();
        return isset($results[0])
            ? $this->reflection->makeModel($results[0], true)
            : null;
    }

    /**
     * @return T
     */
    public function firstOrFail(): ?Model
    {
        return $this->first() ?? throw new RuntimeException('No record found for query: '.$this->toSql());
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
        if ($num === 2 && is_string($column)) {
            if ($relation = $this->reflection->relations[$column] ?? null) {
                $column = $relation->getDestKeyName();
                if ($operator instanceof Model) {
                    $operator = $operator->getProperty($column);
                }
            }
        }

        return parent::where($column, $operator, $value);
    }
}
