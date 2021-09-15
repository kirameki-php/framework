<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Tests\Kirameki\Database\Query\QueryTestCase;

class UpdateBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function testUpdateValue(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1", $sql);
    }

    public function testUpdateValues(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1, 'name' => 'abc'])->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1, `name` = 'abc'", $sql);
    }

    public function testUpdateWithWhere(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->where('lock', 1)->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1 WHERE `lock` = 1", $sql);
    }

    public function testUpdateWithCondition(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->where('lock', 1)->orderByDesc('id')->limit(1)->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1 WHERE `lock` = 1 ORDER BY `id` DESC LIMIT 1", $sql);
    }
}
