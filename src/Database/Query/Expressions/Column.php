<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

class Column extends Expr
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
    public function __construct(string $name, string $as = null)
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
        $name = $formatter->columnize($this->name);
        if ($this->as !== null) {
            $name .= ' AS ' . $this->as;
        }
        return $name;
    }
}
