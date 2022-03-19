<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Generator;
use Kirameki\Core\Config;
use Kirameki\Database\Connection;
use Kirameki\Database\Query\Formatters\Formatter as QueryFormatter;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Schema\Formatters\Formatter as SchemaFormatter;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;
use function preg_match;

/**
 * @mixin Connection
 */
abstract class PdoAdapter implements Adapter
{
    /**
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->config = clone $this->config;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @param string $statement
     * @param array<mixed> $bindings
     * @return Result
     */
    public function query(string $statement, array $bindings = []): Result
    {
        $prepared = $this->execQuery($statement, $bindings);
        $result = $prepared->fetchAll(PDO::FETCH_ASSOC);
        return new Result($result, $prepared->rowCount(...));
    }

    /**
     * @param string $statement
     * @param array<mixed> $bindings
     * @return Generator<mixed>
     */
    public function cursor(string $statement, array $bindings = []): Generator
    {
        $prepared = $this->execQuery($statement, $bindings);
        while (true) {
            $data = $prepared->fetch();
            if ($data === false) {
                if ($prepared->errorCode() === '00000') {
                    break;
                }
                $this->throwException($prepared);
            }
            yield $data;
        }
    }

    /**
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * @param string $id
     */
    public function setSavepoint(string $id): void
    {
        $this->getPdo()->exec('SAVEPOINT '.$this->alphanumeric($id));
    }

    /**
     * @param string $id
     */
    public function rollbackSavepoint(string $id): void
    {
        $this->getPdo()->exec('ROLLBACK TO SAVEPOINT '.$this->alphanumeric($id));
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @param string $statement
     */
    public function execute(string $statement): void
    {
        $this->getPdo()->exec($statement);
    }

    /**
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        try {
            $this->query('SELECT 1 FROM '.$table.' LIMIT 1');
            return true;
        }
        catch (Throwable) {
            return false;
        }
    }

    /**
     * @return QueryFormatter
     */
    abstract public function getQueryFormatter(): QueryFormatter;

    /**
     * @return SchemaFormatter
     */
    public function getSchemaFormatter(): SchemaFormatter
    {
        return new SchemaFormatter();
    }

    /**
     * @param string $statement
     * @param array<mixed> $bindings
     * @return PDOStatement
     */
    protected function execQuery(string $statement, array $bindings): PDOStatement
    {
        $prepared = $this->getPdo()->prepare($statement);
        $prepared->execute($bindings);
        return $prepared;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo; /** @phpstan-ignore-line */
    }

    /**
     * @param string $str
     * @return string
     */
    protected function alphanumeric(string $str): string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $str)) {
            throw new RuntimeException('Invalid string: "'.$str.'". Only alphanumeric characters, "_", and "-" are allowed.');
        }
        return $str;
    }

    /**
     * @param PDOStatement $statement
     * @return void
     */
    protected function throwException(PDOStatement $statement): void
    {
        throw new RuntimeException(implode(' | ', $statement->errorInfo()));
    }
}
