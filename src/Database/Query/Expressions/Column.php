<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

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
     * @param string $expr
     * @param Formatter $formatter
     * @return static
     */
    public static function parse(string $expr, Formatter $formatter): static
    {
        $table = null;
        $column = $expr;
        $as = null;
        if (preg_match('/(\.| as | AS )/', $expr)) {
            $delim = preg_quote($formatter->getIdentifierDelimiter(), null);
            $patterns = [];
            $patterns[] = '(' . $delim . '?(?<table>[^\.'. $delim . ']+)' . $delim . '?\.)?';
            $patterns[] = $delim . '?(?<column>[^ ' . $delim . ']+)' . $delim . '?';
            $patterns[] = '( (AS|as) ' . $delim . '?(?<as>[^'.$delim.']+)' . $delim . '?)?';
            $pattern = '/^' . implode('', $patterns) . '$/';
            $match = null;
            if (preg_match($pattern, $expr, $match)) {
                $table = !empty($match['table']) ? (string)$match['table'] : null;
                $column = $match['column'];
                $as = $match['as'] ?? null;
            }
        }
        return new static($table, $column, $as);
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
     * @return string
     */
    public function prepare(Formatter $formatter): string
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
