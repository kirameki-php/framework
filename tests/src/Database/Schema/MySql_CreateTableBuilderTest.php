<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Schema\Expressions\Raw;
use RuntimeException;

class MySql_CreateTableBuilderTest extends SchemaTestCase
{
    protected string $connection = 'mysql';

    public function testWithNoColumn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table requires at least one column to be defined.');

        $builder = $this->createTableBuilder('users');
        $builder->toString();
    }

    public function testWithoutPrimaryKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table must have at least one column as primary key.');

        $builder = $this->createTableBuilder('users');
        $builder->uuid('id');
        $builder->toString();
    }

    public function testStringColumn(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id VARCHAR(36), PRIMARY KEY (id ASC));', $schema);
    }

    public function testDefaultIntColumn(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt8Column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id TINYINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt16Column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id SMALLINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt32Column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 4)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id INT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt64Column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 8)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testBoolColumn(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->bool('enabled')->default(true);
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, enabled BOOL DEFAULT TRUE, PRIMARY KEY (id ASC));', $schema);
    }

    public function testNotNull(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->notNull();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT NOT NULL, PRIMARY KEY (id ASC));', $schema);
    }

    public function testAutoIncrement(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT AUTO_INCREMENT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testDefaultValue(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->primaryKey()->default('ABC');
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id VARCHAR(36) DEFAULT \'ABC\', PRIMARY KEY (id ASC));', $schema);
    }

    public function testDefaultValueRaw(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->datetime('loginAt')->default(new Raw('CURRENT_TIMESTAMP'));
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, loginAt DATETIME(6) DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id ASC));', $schema);
    }

    public function testComment(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->comment('test\'escaped"');
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT COMMENT \'test\'\'escaped"\', PRIMARY KEY (id ASC));', $schema);
    }
}
