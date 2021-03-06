<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Support\Arr;
use Kirameki\Testing\Concerns\UsesDatabases;
use Tests\Kirameki\TestCase;

class DatabaseTestCase extends TestCase
{
    use UsesDatabases;

    protected array $connections = [];

    public function mysqlConnection(): Connection
    {
        return $this->connections['mysql'] ??= $this->createTempConnection('mysql');
    }

    public function createTable(string $table, callable $callback): void
    {
        $connection = $this->mysqlConnection();
        $builder = new CreateTableBuilder($connection, $table);
        $callback($builder);
        Arr::map($builder->toDdls(), fn($ddl) => $connection->executeSchema($ddl));
    }
}
