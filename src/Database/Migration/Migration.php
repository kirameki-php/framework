<?php

namespace Kirameki\Database\Migration;

use DateTime;
use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Builders\AlterTableBuilder;
use Kirameki\Database\Schema\Builders\DropIndexBuilder;
use Kirameki\Database\Schema\Builders\DropTableBuilder;
use Kirameki\Database\Schema\Builders\RenameTableBuilder;
use Kirameki\Database\Schema\Builders\StatementBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
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
    protected Connection $using;

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
     * @return void
     */
    abstract public function down(): void;

    /**
     * @param string $connection
     * @return $this
     */
    public function using(string $connection)
    {
        $this->using = db()->using($connection);
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
    public function toDdls(): array
    {
        return Arr::flatMap($this->builders, fn(StatementBuilder $b) => $b->toDdls());
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $this->using->query(implode(PHP_EOL, $this->toDdls()));
    }

    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table): CreateTableBuilder
    {
        return $this->builders[] = new CreateTableBuilder($this->using, $table);
    }

    /**
     * @param string $table
     * @return DropTableBuilder
     */
    public function dropTable(string $table): DropTableBuilder
    {
        return $this->builders[] = new DropTableBuilder($this->using, $table);
    }

    /**
     * @param string $table
     * @return AlterTableBuilder
     */
    public function alterTable(string $table): AlterTableBuilder
    {
        return $this->builders[] = new AlterTableBuilder($this->using, $table);
    }

    /**
     * @param string $from
     * @param string $to
     * @return RenameTableBuilder
     */
    public function renameTable(string $from, string $to): RenameTableBuilder
    {
        return $this->builders[] = new RenameTableBuilder($this->using, $from, $to);
    }

    /**
     * @param string $table
     * @return CreateIndexBuilder
     */
    public function createIndex(string $table): CreateIndexBuilder
    {
        $statement = new CreateIndexStatement($table);
        return $this->builders[] = new CreateIndexBuilder($this->using, $statement);
    }

    public function dropIndex(string $table)
    {
        $statement = new DropIndexStatement($table);
        return $this->builders[] = new DropIndexBuilder($this->using, $statement);
    }
}
