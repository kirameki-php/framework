<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Column;

class CreateTableStatement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @var Column[]
     */
    public array $columns;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }
}