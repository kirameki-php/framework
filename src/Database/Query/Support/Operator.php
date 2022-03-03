<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Support;


enum Operator: string
{
    case Equals = '=';
    case LessThan = '<';
    case LessThanOrEqualTo = '<=';
    case GreaterThan = '>';
    case GreaterThanOrEqualTo = '>=';
    case In = 'IN';
    case Between = 'BETWEEN';
    case Exists = 'EXISTS';
    case Like = 'LIKE';
    case Raw = '_RAW_';
    case Range = '_RANGE_';
    case Nested = '_NESTED_';
}
