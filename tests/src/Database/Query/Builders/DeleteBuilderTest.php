<?php

namespace Kirameki\Tests\Database\Query\Builders;

use Kirameki\Tests\Database\Query\QueryTestCase;

class DeleteBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function testDeleteAll()
    {
        $sql = $this->deleteBuilder()->table('User')->toSql();
        static::assertEquals("DELETE FROM `User`", $sql);
    }

    public function testDeleteWhere()
    {
        $sql = $this->deleteBuilder()->table('User')->where('id', 1)->toSql();
        static::assertEquals("DELETE FROM `User` WHERE `id` = 1", $sql);
    }

    public function testDeleteCondition()
    {
        $sql = $this->deleteBuilder()->table('User')->where('id', 1)->orderByDesc('id')->limit(1)->toSql();
        static::assertEquals("DELETE FROM `User` WHERE `id` = 1 ORDER BY `id` DESC LIMIT 1", $sql);
    }
}
