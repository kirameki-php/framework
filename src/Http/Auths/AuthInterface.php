<?php declare(strict_types=1);

namespace Kirameki\Http\Auths;

use Kirameki\Model\AuthUserInterface;
use Kirameki\Model\Model;

interface AuthInterface
{
    /**
     * @return Model|AuthUserInterface|null
     */
    public function validate(): Model|AuthUserInterface|null;

    /**
     * @return bool
     */
    public function validated(): bool;
}
