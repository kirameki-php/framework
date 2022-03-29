<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Statements\BaseStatement;

class Column extends Expr
{
    /**
     * @var string|null
     */
    public readonly ?string $table;

    /**
     * @var string
     */
    public readonly string $column;

    /**
     * @var string|null
     */
    public readonly ?string $as;

    /**
     * @param string|null $table
     * @param string $column
     * @param string|null $as
     */
    public function __construct(?string $table, string $column, string $as = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->as = $as;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter): string
    {
        $table = $this->table;
        $expr = '';

        if ($table !== null) {
            $expr.= $formatter->quote($table) . '.';
        }

        $expr.= $this->column === '*'
            ? $this->column
            : $formatter->quote($this->column);

        if ($this->as !== null) {
            $expr.= ' AS ' . $formatter->quote($this->as);
        }

        return $expr;
    }
}
