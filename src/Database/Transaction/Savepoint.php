<?php

namespace Kirameki\Database\Transaction;

class Savepoint extends Transaction
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        $trigger = new SavepointRollback('Rollback to Savepoint:'.$this->id);
        $trigger->id = $this->id;
        throw $trigger;
    }
}
