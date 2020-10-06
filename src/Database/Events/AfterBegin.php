<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\Transaction;

class AfterBegin extends DatabaseEvent
{
    /**
     * @var Transaction
     */
    public Transaction $transaction;

    /**
     * @param Connection $connection
     * @param Transaction $transaction
     */
    public function __construct(Connection $connection, Transaction $transaction)
    {
        parent::__construct($connection);
        $this->transaction = $transaction;
    }
}
