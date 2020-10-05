<?php

namespace Kirameki\Database\Concerns;

use Kirameki\Database\Connection;
use Kirameki\Database\Transaction\Rollback;
use Kirameki\Database\Transaction\Savepoint;
use Kirameki\Database\Transaction\SavepointRollback;
use Kirameki\Database\Transaction\Transaction;
use Kirameki\Support\Arr;
use RuntimeException;
use Throwable;

/**
 * @mixin Connection
 */
trait Transactions
{
    /**
     * @var Transaction[]
     */
    protected array $txStack = [];

    /**
     * @param callable $callable
     * @param bool $useSavepoint
     * @return mixed
     */
    public function transaction(callable $callable, bool $useSavepoint = false)
    {
        try {
            // Actual transaction
            if (!$this->inTransaction()) {
                $tx = $this->txStack[] = new Transaction();
                return $this->getAdapter()->transaction($callable, $tx);
            }
            // Savepoint if already in transaction and flag is set
            if ($useSavepoint) {
                $savepointId = count($this->txStack) + 1;
                $tx = $this->txStack[] = new Savepoint($savepointId);
                $this->getAdapter()->setSavepoint($savepointId);
                return $callable($tx);
            }
            // Already in transaction so just execute callback
            return $callable($this->txStack[-1]);
        }
        catch (SavepointRollback $rollback) {
            $this->rollbackToSavepoint($rollback->id);
        }
        catch (Rollback $rollback) {
            $this->rollbackTransaction();
        }
        catch (Throwable $throwable) {
            $this->rollbackTransaction();
            throw $throwable;
        }
        return null;
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return count($this->txStack) > 0;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactionStack(): array
    {
        return $this->txStack;
    }

    /**
     * @return Transaction|null
     */
    public function getCurrentTransaction(): ?Transaction
    {
        return Arr::first($this->txStack);
    }

    /**
     * @return void
     */
    protected function rollbackTransaction(): void
    {
        $this->getAdapter()->rollback();
        $this->txStack = [];
    }

    /**
     * @param string $savepoint
     */
    protected function rollbackToSavepoint(string $savepoint): void
    {
        $this->getAdapter()->rollbackSavepoint($savepoint);

        while($tx = array_pop($this->txStack)) {
            if ($tx instanceof Savepoint && $tx->id === $savepoint) {
                return;
            }
        }

        throw new RuntimeException('Invalid Savepoint:'.$savepoint);
    }
}
