<?php

namespace Kirameki\Database\Concerns;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Events\SchemaExecuted;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

/**
 * @mixin Connection
 */
trait Schemas
{
    /**
     * @var SchemaFormatter|null
     */
    protected ?SchemaFormatter $schemaFormatter;

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return $this->schemaFormatter ??= $this->adapter->getSchemaFormatter();
    }

    /**
     * @param string $statement
     */
    public function executeSchema(string $statement): void
    {
        $then = microtime(true);
        $this->adapter->execute($statement);
        $time = microtime(true) - $then;
        $this->dispatchEvent(SchemaExecuted::class, $this, $statement, $time);
    }
}
