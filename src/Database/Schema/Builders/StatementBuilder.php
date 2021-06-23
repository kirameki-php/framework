<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\BaseStatement;
use Kirameki\Support\Concerns\Tappable;

abstract class StatementBuilder implements BuilderInterface
{
    use Tappable;

    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var BaseStatement
     */
    protected $statement;

    /**
     * Do a deep clone of object types
     *
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @return static
     */
    protected function copy()
    {
        return clone $this;
    }

    /**
     * @return string[]
     */
    abstract public function toDdls(): array;

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode(PHP_EOL, $this->toDdls());
    }
}

