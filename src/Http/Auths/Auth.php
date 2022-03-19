<?php declare(strict_types=1);

namespace Kirameki\Http\Auths;

use Kirameki\Model\Model;

/**
 * @template T as Model
 */
interface Auth
{
    /**
     * @return T|null
     */
    public function validate(): Model|null;

    /**
     * @return bool
     */
    public function validated(): bool;

    /**
     * @return void
     */
    public function invalidate(): void;
}
