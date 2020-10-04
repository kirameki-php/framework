<?php

namespace Kirameki\Database\Adapters;

use Generator;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

interface AdapterInterface
{
    /**
     * @return array
     */
    public function getConfig(): array;

    /**
     * @return $this
     */
    public function connect();

    /**
     * @return $this
     */
    public function disconnect();

    /**
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return array
     */
    public function query(string $statement, ?array $bindings = null): array;

    /**
     * @param string $statement
     * @param array|null $bindings
     * @return int
     */
    public function affectingQuery(string $statement, ?array $bindings = null): int;

    /**
     * @param string $statement
     * @param array $bindings
     * @return Generator
     */
    public function cursor(string $statement, array $bindings): Generator;

    /**
     * @param string $statement
     */
    public function executeSchema(string $statement): void;

    /**
     * @param bool $ifNotExist
     */
    public function createDatabase(bool $ifNotExist = true): void;

    /**
     * @param bool $ifNotExist
     */
    public function dropDatabase(bool $ifNotExist = true): void;

    /**
     * @return bool
     */
    public function databaseExists(): bool;

    /**
     * @param string $table
     */
    public function truncate(string $table): void;

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter();

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter();
}
