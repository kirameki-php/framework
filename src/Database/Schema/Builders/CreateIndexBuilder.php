<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Support\Arr;
use RuntimeException;

class CreateIndexBuilder extends StatementBuilder
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
     * @param string $column
     * @param string|null $order
     * @return $this
     */
    public function column(string $column, ?string $order = null): static
    {
        $this->statement->columns[$column] = $order ?? 'ASC';
        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function columns($columns): static
    {
        foreach (Arr::wrap($columns) as $column => $order) {
            is_string($column)
                ? $this->column($column, $order)
                : $this->column($order);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function unique(): static
    {
        $this->statement->unique = true;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): static
    {
        $this->statement->comment = $comment;
        return $this;
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $this->preprocess();
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->createIndexStatement($this->statement),
        ];
    }

    /**
     * @return void
     */
    public function preprocess(): void
    {
        $columns = $this->statement->columns;

        if(empty($columns)) {
            throw new RuntimeException('At least 1 column needs to be defined to create an index.');
        }
    }
}
