<?php

namespace Kirameki\Tests\Database\Schema;

use Kirameki\Database\Migration\MigrationManager;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Tests\Database\DatabaseTestCase;
use Kirameki\Tests\TestCase;

class CreateTableBuilderTest extends DatabaseTestCase
{
    public function testWithNoColumn()
    {
        $builder = new CreateTableBuilder($this->connection('userdata'), 'users');
        $ddls = $builder->toString();

        self::assertEquals($ddls, '');
    }
}
