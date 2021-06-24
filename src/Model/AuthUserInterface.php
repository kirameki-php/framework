<?php declare(strict_types=1);

namespace Kirameki\Model;

interface AuthUserInterface
{
    public function getAuthIdentifierName(): string;
}
