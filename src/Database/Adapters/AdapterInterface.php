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
    public function connect(): static;

    /**
     * @return $this
     */
    public function disconnect(): static;

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
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * @return void
     */
    public function rollback(): void;

    /**
     * @return void
     */
    public function commit(): void;

    /**
     * @param string $id
     */
    public function setSavepoint(string $id): void;

    /**
     * @param string $id
     */
    public function rollbackSavepoint(string $id): void;

    /**
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * @param string $statement
     */
    public function execute(string $statement): void;

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
     * @return bool
     */
    public function tableExists(string $table): bool;

    /**
     * @param string $table
     */
    public function truncate(string $table): void;

    /**
     * @return QueryFormatter
     */
    public function getQueryFormatter(): QueryFormatter;

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter;

    /**
     * @return bool
     */
    public function supportsDdlTransaction(): bool;
}
