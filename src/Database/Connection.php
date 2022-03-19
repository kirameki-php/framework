<?php declare(strict_types=1);

namespace Kirameki\Database;

use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
use Kirameki\Event\EventManager;
use Kirameki\Support\Concerns\Tappable;

class Connection
{
    use Concerns\Queries;
    use Concerns\Schemas;
    use Concerns\Transactions;
    use Tappable;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Adapter
     */
    protected Adapter $adapter;

    /**
     * @var EventManager
     */
    protected EventManager $events;

    /**
     * @param string $name
     * @param Adapter $adapter
     * @param EventManager $events
     */
    public function __construct(string $name, Adapter $adapter, EventManager $events)
    {
        $this->name = $name;
        $this->adapter = $adapter;
        $this->events = $events;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * @param mixed ...$columns
     * @return SelectBuilder
     */
    public function select(...$columns): SelectBuilder
    {
        return (new SelectBuilder($this))->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return (new InsertBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return (new UpdateBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
    {
        return (new DeleteBuilder($this))->table($table);
    }

    /**
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        return $this->adapter->tableExists($table);
    }

    /**
     * @param string $table
     */
    public function truncate(string $table): void
    {
        $this->adapter->truncate($table);
    }
}
