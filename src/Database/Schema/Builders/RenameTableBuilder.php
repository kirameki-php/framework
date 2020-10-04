<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\BaseStatement;
use Kirameki\Database\Support\Expr;

class RenameTableBuilder implements BuilderInterface
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var string
     */
    protected string $from;

    /**
     * @var string
     */
    protected string $to;

    /**
     * @param Connection $connection
     * @param string $from
     * @param string $to
     */
    public function __construct(Connection $connection, string $from, string $to)
    {
        $this->connection = $connection;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->renameTableStatement($this->from, $this->to),
        ];
    }
}