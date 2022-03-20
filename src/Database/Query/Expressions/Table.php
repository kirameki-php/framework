<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Statements\BaseStatement;

class Table extends Expr
{
    /**
     * @var string
     */
    public readonly string $name;

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
        $as = null;
        if (preg_match('/( as | AS )/', $name)) {
            $delim = preg_quote($formatter->getIdentifierDelimiter(), null);
            $tablePatternPart = $delim . '?(?<table>[^ ' . $delim . ']+)' . $delim . '?';
            $asPatternPart = '( (AS|as) ' . $delim . '?(?<as>[^' . $delim . ']+)' . $delim . '?)?';
            $pattern = '/^' . $tablePatternPart . $asPatternPart . '$/';
            $match = null;
            if (preg_match($pattern, $name, $match)) {
                $name = (string)$match['table'];
                $as = $match['as'] ?? null;
            }
        }
        return new static($name, $as);
    }

    /**
     * @param string $name
     * @param string|null $as
     */
    public function __construct(string $name, ?string $as = null)
    {
        $this->name = $name;
        $this->as = $as;
    }

    /**
     * @param Formatter $formatter
     * @param BaseStatement $statement
     * @return string
     */
    public function toSql(Formatter $formatter, BaseStatement $statement): string
    {
        $name = $formatter->quote($this->name);
        if ($this->as !== null) {
            $name .= ' AS ' . $formatter->quote($this->as);
        }
        return $name;
    }
}
