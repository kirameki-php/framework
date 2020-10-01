<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Builders\ColumnBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;

class PrimaryKeyConstraint
{
    /**
     * @var string[]
     */
    public array $columns;

    /**
     * @var string|null
     */
    public ?string $comment;

    /**
     */
    public function __construct()
    {
        $this->columns = [];
        $this->comment = null;
    }
}