<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Support\Expr;
use Tests\Kirameki\Database\DatabaseTestCase;
use RuntimeException;

class ConnectionTest extends DatabaseTestCase
{
    protected function createDummyTable()
    {
        $this->createTable('Dummy', function(CreateTableBuilder $schema) {
            $schema->uuid('id')->primaryKey()->notNull();
        });
    }

    public function testTableExists()
    {
        $this->createDummyTable();

        self::assertTrue($this->mysqlConnection()->tableExists('Dummy'));
    }

}
