<?php

namespace Kirameki\Tests\Database\Migration;

use Kirameki\Database\Migration\MigrationManager;
use Kirameki\Tests\TestCase;

class MigrationManagerTest extends TestCase
{
    public function testMigrateUp()
    {
        $manager = new MigrationManager(__DIR__.'/files');
        dump($manager->inspectUp());
    }
}
