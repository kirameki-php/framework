<?php declare(strict_types=1);

namespace Kirameki\Model;

interface Authenticatable
{
    public function getAuthIdentifierName(): string;
}
