<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;
use Kirameki\Database\Query\Statements\BaseStatement;

class JsonExtract extends Expr
{
    /**
     * @var string
     */
    public readonly string $column;

    /**
     * @var string
     */
    public readonly string $path;

    /**
     * @param string $column
     * @param string $path
     */
    public function __construct(string $column, string $path)
    {
        $this->column = $column;
        $this->path = str_starts_with($path, '$.') ? $path : '$.'.$path;;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter, BaseStatement $statement): string
    {
        return $formatter->formatJsonExtract($this->column, $this->path);
    }
}
