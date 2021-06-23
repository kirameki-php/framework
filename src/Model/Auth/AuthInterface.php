<?php declare(strict_types=1);

namespace Kirameki\Model\Auth;

interface AuthInterface
{
    public function getAuthColumnName(): string;
}
