<?php

namespace Kirameki\Database\Query\Formatters;

use DateTimeInterface;

class MySqlFormatter extends Formatter
{
    protected string $quote = '`';

    /**
     * @param $value
     * @return string
     */
    public function parameter($value)
    {
        if ($value instanceof DateTimeInterface) {
            return '\''.$value->format('Y-m-d H:i:s.u').'\'';
        }
        return $value;
    }
}
