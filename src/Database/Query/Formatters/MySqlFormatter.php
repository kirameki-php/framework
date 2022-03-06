<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use DateTimeInterface;

class MySqlFormatter extends Formatter
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function parameterize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return '\''.$value->format('Y-m-d H:i:s.u').'\'';
        }
        return parent::parameterize($value);
    }
}
