<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

class MySqlFormatter extends Formatter
{
    /**
     * @return string
     */
    protected function getDateTimeFormat(): string
    {
        return 'Y-m-d H:i:s.u';
    }
}
