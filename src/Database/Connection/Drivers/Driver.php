<?php

namespace Kirameki\Database\Connection\Drivers;

use Generator;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

abstract class Driver
{
    protected array $config;

    protected ?QueryFormatter $queryFormatter;

    protected ?SchemaFormatter $schemaFormatter;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return $this
     */
    abstract public function connect();

    /**
     * @return $this
     */
    abstract public function disconnect();

    /**
     * @return bool
     */
    abstract public function isConnected(): bool;

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return array
     */
    abstract public function query(string $statement, ?array $bindings = null): array;

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return int
     */
    abstract public function affectingQuery(string $statement, ?array $bindings = null): int;

    /**
     * @param string $statement
     * @param array $bindings
     * @return Generator
     */
    abstract public function cursor(string $statement, array $bindings): Generator;

    /**
     * @param string $statement
     */
    abstract public function execute(string $statement): void;

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter
    {
        return $this->queryFormatter ??= new QueryFormatter();
    }

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return $this->schemaFormatter ??= new SchemaFormatter();
    }
}
