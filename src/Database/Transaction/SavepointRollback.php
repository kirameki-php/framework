<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

class SavepointRollback extends Rollback
{
    public ?string $id = null;
}
