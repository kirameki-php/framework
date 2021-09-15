<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Tests\Kirameki\Database\Query\QueryTestCase;

class DeleteBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function testDeleteAll(): void
    {
        $sql = $this->deleteBuilder()->table('User')->toSql();
        static::assertEquals("DELETE FROM `User`", $sql);
    }

    public function testDeleteWhere(): void
    {
        $sql = $this->deleteBuilder()->table('User')->where('id', 1)->toSql();
        static::assertEquals("DELETE FROM `User` WHERE `id` = 1", $sql);
    }

    public function testDeleteCondition(): void
    {
        $sql = $this->deleteBuilder()->table('User')->where('id', 1)->orderByDesc('id')->limit(1)->toSql();
        static::assertEquals("DELETE FROM `User` WHERE `id` = 1 ORDER BY `id` DESC LIMIT 1", $sql);
    }
}
