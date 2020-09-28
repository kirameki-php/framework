<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Builders\Column;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;

class CreateTableStatement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @var ColumnDefinition[]
     */
    public array $columns;

    /**
     * @var CreateIndexStatement[]
     */
    public array $indexes;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
        $this->columns = [];
        $this->indexes = [];
    }
}