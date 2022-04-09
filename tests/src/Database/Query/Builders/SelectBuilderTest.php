<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Builders\JoinBuilder;
use Kirameki\Database\Query\Expressions\Raw;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Builders\ConditionBuilder;
use Tests\Kirameki\Database\Query\QueryTestCase;

class SelectBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function testPlain(): void
    {
        $sql = $this->selectBuilder()->columns(new Raw('1'))->toSql();
        static::assertEquals("SELECT 1", $sql);
    }

    public function testFrom(): void
    {
        $sql = $this->selectBuilder()->from('User')->toSql();
        static::assertEquals("SELECT * FROM `User`", $sql);
    }

    public function testFrom_WithAlias(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u`", $sql);
    }

    public function testColumns(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id', 'name')->toSql();
        static::assertEquals("SELECT `id`, `name` FROM `User`", $sql);
    }

    public function testColumns_WithAlias(): void
    {
        $sql = $this->selectBuilder()->from('User as u')->columns('u.*', 'u.name')->toSql();
        static::assertEquals("SELECT `u`.*, `u`.`name` FROM `User` AS `u`", $sql);
    }

    public function testDistinct(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id')->distinct()->toSql();
        static::assertEquals("SELECT DISTINCT `id` FROM `User`", $sql);
    }

    public function test_Join_using_on(): void
    {
        $sql = $this->selectBuilder()->from('User')->join('Device', fn(JoinBuilder $join) => $join->on('User.id', 'Device.userId'))->toSql();
        static::assertEquals("SELECT * FROM `User` JOIN `Device` ON `User`.`id` = `Device`.`userId`", $sql);
    }

    public function test_Join_using_on_and_where(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')
            ->join('Device AS d', fn(JoinBuilder $join) => $join->on('u.id', 'd.userId')->where('id', [1,2]))
            ->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u` JOIN `Device` AS `d` ON `u`.`id` = `d`.`userId` AND `id` IN (?, ?)", $sql);
    }

    public function testJoinOn(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->joinOn('Device AS d', 'u.id', 'd.userId')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u` JOIN `Device` AS `d` ON `u`.`id` = `d`.`userId`", $sql);
    }

    public function testWhere_WithTwoArgs(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', fn() => 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', [3, 4])->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` IN (3, 4)", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', Range::closed(1, 2))->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` >= 1 AND `id` <= 2", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhere_WithThreeArgs(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', '=', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhere_Multiples(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->where('status', 0)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 AND `status` = 0", $sql);
    }

    public function testWhere_Combined(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where(ConditionBuilder::for('id')->lessThan(1)->or()->equals(3))
            ->whereNot('id', -1)
            ->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE (`id` < 1 OR `id` = 3) AND `id` != -1", $sql);
    }

    public function testWhereColumn(): void
    {
        $sql = $this->selectBuilder()->from('User')->whereColumn('User.id', 'Device.userId')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `User`.`id` = `Device`.`userId`", $sql);
    }

    public function testWhereColumn_Aliased(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'Device AS d')->whereColumn('u.id', 'd.userId')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u`, `Device` AS `d` WHERE `u`.`id` = `d`.`userId`", $sql);
    }

    public function testOrderBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderBy('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` ASC", $sql);
    }

    public function testOrderByDesc(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` DESC", $sql);
    }

    public function testGroupBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->groupBy('status')->toSql();
        static::assertEquals("SELECT * FROM `User` GROUP BY `status`", $sql);
    }

    public function testReorder(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->reorder()->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function testWhereLimit(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1", $sql);
    }

    public function testWhereOffset(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->offset(10)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1 OFFSET 10", $sql);
    }

    public function testCombination(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->groupBy('status')->having('status', 1)->limit(2)->orderBy('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 GROUP BY `status` ORDER BY `id` ASC LIMIT 2", $sql);
    }

    public function testClone(): void
    {
        $where = ConditionBuilder::for('id')->equals(1)->or('id')->equals(2);
        $base = $this->selectBuilder()->from('User')->where($where);
        $copy = clone $base;
        $where->or()->in([3,4]); // change $base but should not be reflected on copy
        static::assertEquals("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2 OR `id` IN (3, 4))", $base->toSql());
        static::assertEquals("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2)", $copy->toSql());
        static::assertNotEquals($base->toSql(), $copy->toSql());
    }
}
