<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Statements\BaseStatement;

class Aggregate extends Expr
{
    /**
     * @var string
     */
    public readonly string $function;

    /**
     * @var string
     */
    public readonly string $column;

    /**
     * @var string|null
     */
    public readonly ?string $as;

    /**
     * @param string $function
     * @param string $column
     * @param string|null $as
     */
    public function __construct(string $function, string $column, string $as = null)
    {
        $this->function = $function;
        $this->column = $column;
        $this->as = $as;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter): string
    {
        $expr = $this->function;
        $expr.= $formatter->quote($this->column);
        if ($this->as !== null) {
            $expr.= ' AS ' . $formatter->quote($this->as);
        }
        return $expr;
    }
}
