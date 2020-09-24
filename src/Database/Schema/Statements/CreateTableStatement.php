<?php

namespace Kirameki\Database\Schema\Statements;

class CreateTableStatement
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