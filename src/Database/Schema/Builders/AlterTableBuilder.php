<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\AlterTableStatement;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;

class AlterTableBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->statement = new AlterTableStatement($table);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function addColumn(string $name): ColumnBuilder
    {
        $action = new AlterColumnAction('ADD', new ColumnDefinition($name));
        $this->statement->addAction($action);
        return new AlterColumnBuilder($action);
    }

    /**
     * @param string $name
     * @return AlterColumnBuilder
     */
    public function modifyColumn(string $name): AlterColumnBuilder
    {
        $action = new AlterColumnAction('MODIFY', new ColumnDefinition($name));
        $this->statement->addAction($action);
        return new AlterColumnBuilder($action);
    }

    /**
     * @param string $from
     * @param string $to
     */
    public function renameColumn(string $from, string $to): void
    {
        $this->statement->addAction(new AlterRenameColumnAction($from, $to));
    }

    /**
     * @param string $column
     */
    public function dropColumn(string $column): void
    {
        $this->statement->addAction(new AlterDropColumnAction($column));
    }

    /**
     * @param string|string[] ...$columns
     * @return CreateIndexBuilder
     */
    public function createIndex($columns = []): CreateIndexBuilder
    {
        $statement = new CreateIndexStatement($this->statement->table);
        $builder = new CreateIndexBuilder($this->connection, $statement);
        $builder->columns($columns);
        $this->statement->addAction($statement);
        return $builder;
    }

    /**
     * @param string|string[] ...$columns
     * @return DropIndexBuilder
     */
    public function dropIndex($columns = []): DropIndexBuilder
    {
        $statement = new DropIndexStatement($this->statement->table);
        $bulider = new DropIndexBuilder($this->connection, $statement);
        $bulider->columns($columns);
        $this->statement->addAction($bulider);
        return $bulider;
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        $ddls = [];
        foreach ($this->statement->actions as $action) {
            if ($action instanceof AlterColumnAction) {
                if ($action->isAdd()) {
                    $ddls[] = $formatter->addColumnAction($action);
                } else {
                    $ddls[] = $formatter->modifyColumnAction($action);
                }
            }
            elseif ($action instanceof AlterDropColumnAction) {
                $ddls[] = $formatter->dropColumnAction($action);
            }
            elseif ($action instanceof AlterRenameColumnAction) {
                $ddls[] = $formatter->renameColumnAction($action);
            }
            elseif ($action instanceof CreateIndexStatement) {
                $ddls[] = $formatter->createIndexStatement($action);
            }
            elseif ($action instanceof DropIndexStatement) {
                $ddls[] = $formatter->dropIndexStatement($action);
            }
        }
        return $ddls;
    }
}