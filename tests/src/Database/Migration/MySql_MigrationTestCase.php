<?php

namespace Kirameki\Tests\Database\Migration;

use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Connection;
use Kirameki\Tests\Database\DatabaseTestCase;

class MySql_MigrationTestCase extends DatabaseTestCase
{
    /**
     * @before
     */
    protected function setUpDatabase(): void
    {
        $adapter = $this->migrationConnection()->getAdapter();
        $adapter->dropDatabase();
        $adapter->createDatabase();
    }

    /**
     * @after
     */
    protected function tearDownDatabase(): void
    {
        $this->migrationConnection()->getAdapter()->dropDatabase();
    }

    protected function migrationConnection(): Connection
    {
        $adapter = new MySqlAdapter(['host' => 'mysql', 'database' => 'migration_test']);
        $connection = new Connection('migration_test', $adapter, event());
        db()->addConnection($connection);
        return $connection;
    }
}
