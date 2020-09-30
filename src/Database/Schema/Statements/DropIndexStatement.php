<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Builders\ColumnBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;

class DropIndexStatement extends BaseStatement
{
    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string[]
     */
    public array $columns;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        parent::__construct($table);
        $this->columns = [];
    }
}