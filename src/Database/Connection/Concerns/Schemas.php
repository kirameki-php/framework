<?php

namespace Kirameki\Database\Connection\Concerns;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;

/**
 * @mixin Connection
 */
trait Schemas
{
    /**
     * @param string $table
     * @return CreateTableBuilder
     */
    public function createTable(string $table)
    {
        return new CreateTableBuilder($this, $table);
    }
}
