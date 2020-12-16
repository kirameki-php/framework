<?php

namespace Kirameki\Tests\Database\Migration;

use Kirameki\Database\Migration\MigrationManager;

class MigrationManagerTest extends MySql_MigrationTestCase
{
    public function testMigrateUp()
    {
        $manager = new MigrationManager(__DIR__.'/files');
        $manager->up();

        $connection = db()->using('migration_test');

        self::assertTrue($connection->tableExists('User'));
    }
}
