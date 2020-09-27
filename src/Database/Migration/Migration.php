<?php

namespace Kirameki\Database\Migration;

use DateTime;
use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Builders\Builder;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;

abstract class Migration
{
    /**
     * @var string|null
     */
    protected ?string $time;

    /**
     * @var Builder[]
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
     * @return Builder[]
     */
    public function getBuilders(): array
    {
        return $this->builders;
    }

    /**
     * @return string
     */
    public function toDdl()
    {
        $defs = array_map(fn(Builder $b) => $b->toSql(), $this->builders);
        return implode(PHP_EOL, $defs);
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->connection->query($this->toDdl());
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return $this->builders[]= new CreateTableBuilder($this->connection, $table);
    }
}
