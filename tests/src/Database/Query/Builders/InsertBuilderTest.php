<?php

namespace Tests\Kirameki\Database\Query\Builders;

use Carbon\Carbon;
use Tests\Kirameki\Database\Query\QueryTestCase;

class InsertBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function testInsertValue()
    {
        $sql = $this->insertBuilder()->table('User')->value(['status'=> 1, 'name' => 'abc'])->toSql();
        static::assertEquals("INSERT INTO `User` (`status`, `name`) VALUES (1, 'abc')", $sql);
    }

    public function testInsertValues()
    {
        $sql = $this->insertBuilder()->table('User')->values([['name' => 'abc'], ['name' => 'def']])->toSql();
        static::assertEquals("INSERT INTO `User` (`name`) VALUES ('abc'), ('def')", $sql);
    }

    public function testInsertPartialValues()
    {
        $sql = $this->insertBuilder()->table('User')->values([['status'=> 1], ['name' => 'abc']])->toSql();
        static::assertEquals("INSERT INTO `User` (`status`, `name`) VALUES (1, NULL), (NULL, 'abc')", $sql);
    }

    public function testInsertInteger()
    {
        $sql = $this->insertBuilder()->table('User')->values([['id' => 1], ['id' => 2]])->toSql();
        static::assertEquals("INSERT INTO `User` (`id`) VALUES (1), (2)", $sql);
    }

    public function testInsertString()
    {
        $sql = $this->insertBuilder()->table('User')->values([['name' => 'a'], ['name' => 'b']])->toSql();
        static::assertEquals("INSERT INTO `User` (`name`) VALUES ('a'), ('b')", $sql);
    }

    public function testInsertDateTime()
    {
        $sql = $this->insertBuilder()->table('User')->value(['createdAt' => new Carbon('2020-01-01T01:12:34.56789Z')])->toSql();
        static::assertEquals("INSERT INTO `User` (`createdAt`) VALUES ('2020-01-01 01:12:34.567890')", $sql);
    }
}
