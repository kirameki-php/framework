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
     * @param string|null $as
     */
    public function __construct(string $name, ?string $as = null)
    {
        $this->name = $name;
        $this->as = $as;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter): string
    {
        $name = $formatter->quote($this->name);
        if ($this->as !== null) {
            $name .= ' AS ' . $formatter->quote($this->as);
        }
        return $name;
    }
}
