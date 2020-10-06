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
                $this->getAdapter()->beginTransaction();
                $result = $callable($tx);
                $this->getAdapter()->commit();
                return $result;
            }
            // Savepoint if already in transaction and flag is set
            if ($useSavepoint) {
                $savepointId = count($this->txStack) + 1;
                $tx = $this->txStack[] = new Savepoint($savepointId);
                $this->getAdapter()->setSavepoint($savepointId);
                return $callable($tx);
            }
            // Already in transaction so just execute callback
            $this->txStack[] = null;
            return $callable($this->txStack[-1]);
        }

        // This is thrown when user calls rollback() on Savepoint instance.
        catch (SavepointRollback $rollback) {
            $this->rollbackToSavepoint($rollback->id);
        }

        // This is thrown when user calls rollback() on Transaction instances.
        // We will propagate up to the first transaction block and do a rollback there.
        catch (Rollback $rollback) {
            $this->rollback($rollback);
        }

        // We will propagate up to the first transaction block, rollback and then rethrow.
        catch (Throwable $throwable) {
            $this->rollbackAndThrow($throwable);
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
     * @param Throwable $throwable
     */
    protected function rollbackAndThrow(Throwable $throwable): void
    {
        array_pop($this->txStack);

        if (empty($this->txStack)) {
            $this->getAdapter()->rollback();
        }

        throw $throwable;
    }

    /**
     * @param Rollback $rollback
     */
    protected function rollback(Rollback $rollback): void
    {
        array_pop($this->txStack);

        if (empty($this->txStack)) {
            $this->getAdapter()->rollback();
            return;
        }

        throw $rollback;
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
