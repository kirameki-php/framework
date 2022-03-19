<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Statements\BaseStatement;

abstract class Expr
{
    /**
     * @param Formatter $formatter
     * @param BaseStatement $statement
     * @return string
     */
    abstract public function toSql(Formatter $formatter, BaseStatement $statement): string;
}
