<?php

namespace Kirameki\Database\Connection\Concerns;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

/**
 * @mixin Connection
 */
trait Schemas
{
    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return $this->driver->getSchemaFormatter();
    }

    /**
     * @param string $statement
     */
    public function execSchema(string $statement): void
    {
        $this->driver->execute($statement);
    }
}
