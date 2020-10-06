<?php

namespace Kirameki\Database\Events;

use Kirameki\Database\Transaction\Transaction;
use Kirameki\Event\Event;

class AfterBegin implements Event
{
    /**
     * @var Transaction
     */
    public Transaction $transaction;

    /**
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
