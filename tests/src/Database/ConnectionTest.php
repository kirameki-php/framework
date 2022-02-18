<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;

class ConnectionTest extends DatabaseTestCase
{
    protected function createDummyTable(): void
    {
        $this->createTable('Dummy', function(CreateTableBuilder $schema) {
            $schema->uuid('id')->primaryKey()->notNull();
        });
    }

    public function testTableExists(): void
    {
        $this->createDummyTable();

        self::assertTrue($this->mysqlConnection()->tableExists('Dummy'));
    }

    public function testCursor(): void
    {
        $this->createDummyTable();
        $conn = $this->mysqlConnection();
        $conn->insertInto('Dummy')->value(['id' => 'test'])->execute();

        $count = 0;
        foreach ($this->mysqlConnection()->cursor('SELECT * FROM Dummy') as $value) {
            $this->assertEquals(['id' => 'test'], $value);
            $count++;
        }
        $this->assertEquals(1, $count);
    }
}
