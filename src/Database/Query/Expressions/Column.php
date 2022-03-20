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
     * @param string $name
     * @param Formatter $formatter
     * @return static
     */
    public static function parse(string $name, Formatter $formatter): static
    {
        $table = null;
        $as = null;
        if (preg_match('/(\.| as | AS )/', $name)) {
            $delim = preg_quote($formatter->getIdentifierDelimiter(), null);
            $tablePatternPart = '(' . $delim . '?(?<table>[^\.'. $delim . ']+)' . $delim . '?\.)?';
            $columnPatternPart = $delim . '?(?<column>[^ ' . $delim . ']+)' . $delim . '?';
            $asPatternPart = '( (AS|as) ' . $delim . '?(?<as>[^'.$delim.']+)' . $delim . '?)?';
            $pattern = '/^' . $tablePatternPart . $columnPatternPart . $asPatternPart . '$/';
            $match = null;
            if (preg_match($pattern, $name, $match)) {
                $table = !empty($match['table']) ? (string)$match['table'] : null;
                $name = $match['column'];
                $as = $match['as'] ?? null;
            }
        }
        return new static($table, $name, $as);
    }

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
     * @param BaseStatement $statement
     * @return string
     */
    public function toSql(Formatter $formatter, BaseStatement $statement): string
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
