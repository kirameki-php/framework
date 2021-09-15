<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use DateTimeInterface;

class MySqlFormatter extends Formatter
{
    /**
     * @param $value
     * @return string
     */
    public function parameter($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return '\''.$value->format('Y-m-d H:i:s.u').'\'';
        }
        return (string) $value;
    }
}
