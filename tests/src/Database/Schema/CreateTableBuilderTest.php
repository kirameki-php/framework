<?php

namespace Kirameki\Tests\Database\Schema;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Support\Expr;
use Kirameki\Tests\Database\DatabaseTestCase;
use RuntimeException;

class CreateTableBuilderTest extends DatabaseTestCase
{
    public function testWithNoColumn()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table requires at least one column to be defined.');

        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->toString();
    }

    public function testWithoutPrimaryKey()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table must have at least one column as primary key.');

        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->uuid('id');
        $builder->toString();
    }

    public function testStringColumn()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->uuid('id')->primaryKey();
        $schema = $builder->toString();
        $this->connection('mysql')->execute($schema);
        static::assertEquals('CREATE TABLE users (id VARCHAR(36), PRIMARY KEY (id ASC));', $schema);
    }

    public function testDefaultIntColumn()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id')->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt8Column()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id', 1)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id TINYINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt16Column()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id', 2)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id SMALLINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt32Column()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id', 4)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id INT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testInt64Column()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id', 8)->primaryKey();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testBoolColumn()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id')->primaryKey();
        $builder->bool('enabled')->default(true);
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, enabled BOOL DEFAULT TRUE, PRIMARY KEY (id ASC));', $schema);
    }

    public function testNotNull()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id')->primaryKey()->notNull();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT NOT NULL, PRIMARY KEY (id ASC));', $schema);
    }

    public function testAutoIncrement()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT AUTO_INCREMENT, PRIMARY KEY (id ASC));', $schema);
    }

    public function testDefaultValue()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->uuid('id')->primaryKey()->default('ABC');
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id VARCHAR(36) DEFAULT \'ABC\', PRIMARY KEY (id ASC));', $schema);
    }

    public function testDefaultValueRaw()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id')->primaryKey();
        $builder->datetime('loginAt')->default(Expr::raw('CURRENT_TIMESTAMP'));
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT, loginAt DATETIME(6) DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id ASC));', $schema);
    }

    public function testComment()
    {
        $builder = new CreateTableBuilder($this->connection('mysql'), 'users');
        $builder->int('id')->primaryKey()->comment('test\'escaped"');
        $schema = $builder->toString();
        static::assertEquals('CREATE TABLE users (id BIGINT COMMENT \'test\'\'escaped"\', PRIMARY KEY (id ASC));', $schema);
    }

}
