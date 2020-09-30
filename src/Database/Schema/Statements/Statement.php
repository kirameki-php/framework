<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Builders\ColumnBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;

class Statement
{
    /**
     * @var string
     */
    public string $table;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }
}
