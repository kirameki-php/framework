<?php

namespace Kirameki\Tests\Database\Migration;

use Kirameki\Database\Migration\MigrationManager;
use Kirameki\Tests\Database\DatabaseTestCase;
use Kirameki\Tests\TestCase;

class MigrationManagerTest extends DatabaseTestCase
{
    public function testMigrateUp()
    {
        $manager = new MigrationManager(__DIR__.'/files');
        dump($this->connection('mysql')->query('SHOW DATABASES like \'maysql\''));
//        dump($manager->inspectUp());
    }
}
