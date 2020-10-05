<?php

namespace Kirameki\Database\Transaction;

class Transaction
{
    /**
     * @return void
     */
    public function rollback(): void
    {
        throw new Rollback('Rollback Transaction');
    }
}
