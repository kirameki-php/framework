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
        $adapter = new MySqlAdapter(['host' => 'mysql', 'database' => 'migration_test']);
        $connection = new Connection('migration_test', $adapter);
        db()->addConnection($connection);
        $adapter->dropDatabase();
        $adapter->createDatabase();
    }

    /**
     * @after
     */
    protected function tearDownDatabase(): void
    {
        db()->using('migration_test')->getAdapter()->dropDatabase();
    }
}
