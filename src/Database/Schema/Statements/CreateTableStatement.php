<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Builders\ColumnBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;

class CreateTableStatement extends BaseStatement
{
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
        parent::__construct($table);
        $this->columns = [];
        $this->indexes = [];
    }
}