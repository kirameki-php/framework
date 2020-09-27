<?php

namespace Kirameki\Database\Connection\Concerns;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

/**
 * @mixin Connection
 */
trait Schemas
{
    protected ?SchemaFormatter $schemaFormatter;

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter()
    {
        return $this->schemaFormatter ??= new SchemaFormatter($this);
    }

    /**
     * @param string $statement
     */
    public function execSchema(string $statement)
    {
        $this->getPdo()->exec($statement);
    }
}
