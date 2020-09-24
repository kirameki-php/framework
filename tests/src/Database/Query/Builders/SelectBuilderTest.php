<?php

namespace Kirameki\Tests\Database\Query\Builders;

use Kirameki\Database\Query\Expr;
use Kirameki\Database\Query\Range;
use Kirameki\Database\Query\Condition;
use Kirameki\Tests\Database\Query\QueryTestCase;

class SelectBuilderTest extends QueryTestCase
{
    public function testPlain()
    {
        $sql = $this->selectBuilder()->columns(Expr::raw(1))->toSql();
        static::assertEquals("SELECT 1", $sql);
    }

    public function testFrom()
    {
        $sql = $this->selectBuilder()->table('User')->toSql();
        static::assertEquals("SELECT * FROM `User`", $sql);
    }

    public function testFrom_WithAlias()
    {
        $sql = $this->selectBuilder()->table('User', 'u')->toSql();
        static::assertEquals("SELECT * FROM `User` AS u", $sql);
    }

    public function testColumns()
    {
        $sql = $this->selectBuilder()->table('User')->columns('id', 'name')->toSql();
        static::assertEquals("SELECT `id`, `name` FROM `User`", $sql);
    }

    public function testDistinct()
    {
        $sql = $this->selectBuilder()->table('User')->columns('id')->distinct()->toSql();
        static::assertEquals("SELECT DISTINCT `id` FROM `User`", $sql);
    }

    public function testWhere_WithTwoArgs()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', fn(Condition $w) => $w->equals(1))->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);

        $sql = $this->selectBuilder()->table('User')->where('id', [3, 4])->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` IN (3, 4)", $sql);

        $sql = $this->selectBuilder()->table('User')->where('id', Range::closed(1, 2))->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` >= 1 AND `id` <= 2", $sql);

        $sql = $this->selectBuilder()->table('User')->where('id', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhere_WithThreeArgs()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', '=', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhere_Multiples()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->where('status', 0)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 AND `status` = 0", $sql);
    }

    public function testWhere_Combined()
    {
        $sql = $this->selectBuilder()->table('User')
            ->where('id', fn(Condition $w) => $w->lessThan(1)->or()->equals(3))
            ->whereNot('id', -1)
            ->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE (`id` < 1 OR `id` = 3) AND `id` != -1", $sql);
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

    public function testGroupBy()
    {
        $sql = $this->selectBuilder()->table('User')->groupBy('status')->toSql();
        static::assertEquals("SELECT * FROM `User` GROUP BY `status`", $sql);
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

    public function testCombination()
    {
        $sql = $this->selectBuilder()->table('User')->where('id', 1)->groupBy('status')->having('status', 1)->limit(2)->orderBy('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 GROUP BY `status` ORDER BY `id` ASC LIMIT 2", $sql);
    }

    public function testClone()
    {
        $where = Condition::for('id')->eq(1)->or('id')->eq(2);
        $base = $this->selectBuilder()->table('User')->where($where);
        $copy = clone $base;
        $where->or()->in([3,4]); // change $base but should not be reflected on copy
        static::assertEquals("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2 OR `id` IN (3, 4))", $base->toSql());
        static::assertEquals("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2)", $copy->toSql());
        static::assertNotEquals($base->toSql(), $copy->toSql());
    }
}
