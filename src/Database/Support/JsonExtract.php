<?php declare(strict_types=1);

namespace Kirameki\Database\Support;

use Kirameki\Database\Query\Formatters\Formatter;

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
     * @var bool
     */
    public readonly bool $unwrap;

    /**
     * @param string $column
     * @param string $path
     * @param bool $unwrap
     */
    public function __construct(string $column, string $path, bool $unwrap = false)
    {
        $this->column = $column;
        $this->path = $path;
        $this->unwrap = $unwrap;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter): string
    {
        return $formatter->formatJsonExtract($this->column, $this->path, $this->unwrap);
    }
}
