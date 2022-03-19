<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

class PostgreSqlFormatter extends Formatter
{
    /**
     * @return string
     */
    public function getIdentifierDelimiter(): string
    {
        return '"';
    }
}
