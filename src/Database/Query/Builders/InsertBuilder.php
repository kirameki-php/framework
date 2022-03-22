<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Query\Statements\InsertStatement;
use RuntimeException;
use Traversable;

/**
 * @property InsertStatement $statement
 */
class InsertBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new InsertStatement();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function table(string $name): static
    {
        $this->statement->table = $name;
        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @return $this
     */
    public function value(array $data): static
    {
        $this->statement->dataset = [$data];
        return $this;
    }

    /**
     * @param iterable<array<string, mixed>> $dataset
     * @return $this
     */
    public function values(iterable $dataset): static
    {
        $dataset = ($dataset instanceof Traversable) ? iterator_to_array($dataset) : $dataset;
        $this->statement->dataset = $dataset;
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function returning(string ...$columns): static
    {
        $this->statement->returning = $columns;
        return $this;
    }

    /**
     * @return Result
     */
    public function execute(): Result
    {
        if (empty($this->statement->dataset)) {
            throw new RuntimeException('Values must be set in order to execute an insert query');
        }
        return parent::execute();
    }

    /**
     * @return string
     */
    public function prepare(): string
    {
        return $this->getQueryFormatter()->formatInsertStatement($this->statement);
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getQueryFormatter()->formatBindingsForInsert($this->statement);
    }
}
