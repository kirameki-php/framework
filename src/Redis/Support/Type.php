<?php declare(strict_types=1);

namespace Kirameki\Redis\Support;

enum Type: string
{
    case None = 'none';
    case String = 'string';
    case List = 'list';
    case Set = 'set';
    case ZSet = 'zset';
    case Hash = 'hash';
    case Stream = 'stream';
}
