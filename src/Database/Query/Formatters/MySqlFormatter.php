<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use DateTimeInterface;

class MySqlFormatter extends Formatter
{
    protected string $quote = '`';

    /**
     * @param $value
     * @return mixed
     */
    public function parameter($value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return '\''.$value->format('Y-m-d H:i:s.u').'\'';
        }
        return $value;
    }
}
