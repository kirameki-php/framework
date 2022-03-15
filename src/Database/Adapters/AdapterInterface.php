<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Generator;
use Kirameki\Core\Config;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;

interface AdapterInterface
{
    /**
     * @return Config
     */
    public function getConfig(): Config;

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
     * @param array<mixed>|null $bindings
     * @return Result
     */
    public function query(string $statement, ?array $bindings = null): Result;

    /**
     * @param string $statement
     * @param array<mixed> $bindings
     * @return Generator<mixed>
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
     * @return void
     */
    public function setSavepoint(string $id): void;

    /**
     * @param string $id
     * @return void
     */
    public function rollbackSavepoint(string $id): void;

    /**
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * @param string $statement
     * @return void
     */
    public function execute(string $statement): void;

    /**
     * @param bool $ifNotExist
     * @return void
     */
    public function createDatabase(bool $ifNotExist = true): void;

    /**
     * @param bool $ifNotExist
     * @return void
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
     * @return void
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
