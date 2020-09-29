<?php

namespace Kirameki\Database\Migration;

use DateTime;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Builders\StatementBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Support\Arr;
use Kirameki\Support\Concerns\Tappable;

abstract class Migration
{
    use Tappable;

    /**
     * @var string|null
     */
    protected ?string $time;

    /**
     * @var StatementBuilder[]
     */
    protected array $builders;

    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @param string|null $time
     */
    public function __construct(?string $time = null)
    {
        $this->time = $time;
        $this->builders = [];
    }

    /**
     * @return void
     */
    abstract public function up(): void;

    /**
     * @param string $connection
     * @return $this
     */
    public function on(string $connection)
    {
        $this->connection = db()->on($connection);
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedTime(): DateTime
    {
        return DateTime::createFromFormat('YmdHis', $this->time);
    }

    /**
     * @return StatementBuilder[]
     */
    public function getBuilders(): array
    {
        return $this->builders;
    }

    /**
     * @return string[]
     */
    public function toDdls()
    {
        return Arr::flatMap($this->builders, fn(StatementBuilder $b) => $b->toDdls());
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->connection->query(implode(PHP_EOL, $this->toDdls()));
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return $this->builders[]= new CreateTableBuilder($this->connection, $table);
    }

    /**
     * @param string $table
     * @param string|string[] $columns
     * @return CreateIndexBuilder
     */
    public function createIndex(string $table, $columns): CreateIndexBuilder
    {
        $statement = new CreateIndexStatement($table, Arr::wrap($columns));
        $builder = new CreateIndexBuilder($this->connection, $statement);
        return $this->builders[]= $builder;
    }
}
