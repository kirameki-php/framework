<?php declare(strict_types=1);

namespace Kirameki\Database\Support;

use Kirameki\Database\Query\Formatters\Formatter;

abstract class Expr
{
    /**
     * @param string $value
     * @return Raw
     */
    public static function raw(string $value): Raw
    {
        return new Raw($value);
    }

    /**
     * @param string $column
     * @param string $path
     * @return JsonExtract
     */
    public static function jsonExtract(string $column, string $path): JsonExtract
    {
        return new JsonExtract($column, $path);
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    abstract public function toSql(Formatter $formatter): string;
}
