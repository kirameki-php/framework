<?php

namespace Kirameki\Tests\Database\Query\Builders;

use Kirameki\Database\Connection\MySqlConnection;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Range;
use Kirameki\Database\Query\WhereClause;
use Kirameki\Tests\TestCase;

class SelectBuilderTest extends TestCase
{
    protected function selectBuilder()
    {
        return new SelectBuilder(new MySqlConnection('test', []));
    }

    public function testPlain()
    {
        $sql = $this->selectBuilder()->columns(1)->toSql();
        static::assertEquals("SELECT 1", $sql);
    }

    public function testFrom()
    {
        $sql = $this->selectBuilder()->table('User')->toSql();
        static::assertEquals("SELECT * FROM `User`", $sql);
    }

    public function testFromWithAlias()
    {
        $sql = $this->selectBuilder()->table('User', 'u')->toSql();
        static::assertEquals("SELECT * FROM `User` AS u", $sql);
    }

    public function testWhereWithTwoArgs()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', fn(WhereClause $w) => $w->eq(1))->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);

        $sql = $this->selectBuilder()->table('User')->where('id', [3, 4])->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` IN (3, 4)", $sql);

        $sql = $this->selectBuilder()->table('User')->where('id', Range::closed(1, 2))->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` >= 1 AND `id` <= 2", $sql);

        $sql = $this->selectBuilder()->table('User')->where('id', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhereWithThreeArgs()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', '=', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testOrderBy()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->orderBy('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` ASC", $sql);
    }

    public function testOrderByDesc()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->orderByDesc('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` DESC", $sql);
    }

    public function testReorder()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->orderByDesc('id')->reorder()->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhereLimit()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->limit(1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1", $sql);
    }

    public function testWhereOffset()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->limit(1)->offset(10)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1 OFFSET 10", $sql);
    }
}
