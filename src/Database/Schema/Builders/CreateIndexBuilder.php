<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;

class CreateIndexBuilder extends Builder
{
    /**
     * @param Connection $connection
     * @param CreateIndexStatement $statement
     */
    public function __construct(Connection $connection, CreateIndexStatement $statement)
    {
        $this->connection = $connection;
        $this->statement = $statement;
    }

    /**
     * @return $this
     */
    public function unique()
    {
        $this->statement->unique = true;
        return $this;
    }

    /**
     * @param string $order
     * @return $this
     */
    public function order(string $order)
    {
        $this->statement->order = $order;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment)
    {
        $this->statement->comment = $comment;
        return $this;
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        return [$formatter->statementForCreateIndex($this->statement)];
    }
}
