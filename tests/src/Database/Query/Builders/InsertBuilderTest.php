<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Support\Time;
use Tests\Kirameki\Database\Query\QueryTestCase;

class InsertBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function testInsertValue(): void
    {
        $sql = $this->insertBuilder()->table('User')->value(['status'=> 1, 'name' => 'abc'])->toSql();
        static::assertEquals("INSERT INTO `User` (`status`, `name`) VALUES (1, 'abc')", $sql);
    }

    public function testInsertValues(): void
    {
        $sql = $this->insertBuilder()->table('User')->values([['name' => 'abc'], ['name' => 'def']])->toSql();
        static::assertEquals("INSERT INTO `User` (`name`) VALUES ('abc'), ('def')", $sql);
    }

    public function testInsertPartialValues(): void
    {
        $sql = $this->insertBuilder()->table('User')->values([['status'=> 1], ['name' => 'abc']])->toSql();
        static::assertEquals("INSERT INTO `User` (`status`, `name`) VALUES (1, NULL), (NULL, 'abc')", $sql);
    }

    public function testInsertInteger(): void
    {
        $sql = $this->insertBuilder()->table('User')->values([['id' => 1], ['id' => 2]])->toSql();
        static::assertEquals("INSERT INTO `User` (`id`) VALUES (1), (2)", $sql);
    }

    public function testInsertString(): void
    {
        $sql = $this->insertBuilder()->table('User')->values([['name' => 'a'], ['name' => 'b']])->toSql();
        static::assertEquals("INSERT INTO `User` (`name`) VALUES ('a'), ('b')", $sql);
    }

    public function testInsertDateTime(): void
    {
        $sql = $this->insertBuilder()->table('User')->value(['createdAt' => new Time('2020-01-01T01:12:34.56789Z')])->toSql();
        static::assertEquals("INSERT INTO `User` (`createdAt`) VALUES ('2020-01-01 01:12:34.567890')", $sql);
    }
}
