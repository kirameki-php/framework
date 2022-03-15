<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;

use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Support\Expr;

class Column extends Expr
{
    /**
     * @var string
     */
    public readonly string $name;

    /**
     * @var string|null
     */
    public readonly ?string $alias;

    /**
     * @param string $name
     * @param string|null $alias
     */
    public function __construct(string $name, string $alias = null)
    {
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter): string
    {
        $name = $formatter->columnize($this->name);
        if ($this->alias !== null) {
            $name .= ' AS ' . $this->alias;
        }
        return $name;
    }
}
