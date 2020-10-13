<?php

namespace Kirameki\Model;

use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Support\Arr;

class QueryBuilder extends SelectBuilder
{
    protected Reflection $reflection;

    public function __construct(DatabaseManager $db, Reflection $reflection)
    {
        $connection = $db->using($reflection->connection);
        parent::__construct($connection);
        $this->reflection = $reflection;

        $this->from($reflection->table);
    }

    /**
     * @return ModelCollection
     */
    public function all(): ModelCollection
    {
        $reflection = $this->reflection;
        $results = $this->execSelect();
        $models = Arr::map($results, static fn($props) => $reflection->makeModel($props, true));
        return new ModelCollection($reflection, $models);
    }

    /**
     * @return Model|null
     */
    public function one()
    {
        $results = $this->copy()->limit(1)->execSelect();
        return isset($results[0])
            ? $this->reflection->makeModel($results[0], true)
            : null;
    }
}
