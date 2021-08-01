<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\BaseStatement;

class DropTableBuilder extends StatementBuilder
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->statement = new BaseStatement($table);
    }

    /**
     * @return string[]
     */
    public function toDdls(): array
    {
        $formatter = $this->connection->getSchemaFormatter();
        return [
            $formatter->dropTableStatement($this->statement)
        ];
    }
}