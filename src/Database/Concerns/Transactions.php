<?php

namespace Kirameki\Database\Concerns;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\AfterBegin;
use Kirameki\Database\Events\AfterCommit;
use Kirameki\Database\Events\AfterRollback;
use Kirameki\Database\Events\AfterSavepoint;
use Kirameki\Database\Events\AfterSavepointRollback;
use Kirameki\Database\Transaction\Rollback;
use Kirameki\Database\Transaction\Savepoint;
use Kirameki\Database\Transaction\SavepointRollback;
use Kirameki\Database\Transaction\Transaction;
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
     * @param callable $callback
     * @param bool $useSavepoint
     * @return mixed
     */
    public function transaction(callable $callback, bool $useSavepoint = false)
    {
        try {
            // Actual transaction
            if (!$this->inTransaction()) {
                $this->runInTransaction($callback);
            }
            // Savepoint if already in transaction and flag is set
            if ($useSavepoint) {
                $this->runInSavepoint($callback);
            }
            // Already in transaction so just execute callback
            $this->txStack[] = null;
            return $callback($this->txStack[-1]);
        }

        // This is thrown when user calls rollback() on Savepoint instance.
        catch (SavepointRollback $rollback) {
            $this->rollbackToSavepoint($rollback);
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
     * @param callable $callback
     * @return mixed
     */
    protected function runInTransaction(callable $callback)
    {
        $tx = $this->txStack[] = new Transaction();

        $this->getAdapter()->beginTransaction();

        $this->dispatchEvent(AfterBegin::class, $tx);

        $result = $callback($tx);

        $this->getAdapter()->commit();

        $this->dispatchEvent(AfterCommit::class);

        return $result;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    protected function runInSavepoint(callable $callback)
    {
        $savepointId = count($this->txStack) + 1;

        $tx = $this->txStack[] = new Savepoint($savepointId);

        $this->getAdapter()->setSavepoint($savepointId);

        $this->dispatchEvent(AfterSavepoint::class, $tx);

        return $callback($tx);
    }

    /**
     * @param SavepointRollback $rollback
     */
    protected function rollbackToSavepoint(SavepointRollback $rollback): void
    {
        $this->getAdapter()->rollbackSavepoint($rollback->id);

        $this->dispatchEvent(AfterSavepointRollback::class, $rollback);

        while($tx = array_pop($this->txStack)) {
            if ($tx instanceof Savepoint && $tx->id === $rollback->id) {
                return;
            }
        }

        throw new RuntimeException('Invalid Savepoint:'.$rollback->id);
    }

    /**
     * @param Rollback $rollback
     */
    protected function rollback(Rollback $rollback): void
    {
        array_pop($this->txStack);

        if (empty($this->txStack)) {
            $this->getAdapter()->rollback();
            $this->dispatchEvent(AfterRollback::class, $rollback);
            return;
        }

        throw $rollback;
    }

    /**
     * @param Throwable $throwable
     */
    protected function rollbackAndThrow(Throwable $throwable): void
    {
        array_pop($this->txStack);

        if (empty($this->txStack)) {
            $this->getAdapter()->rollback();
            $this->dispatchEvent(AfterRollback::class, $throwable);
        }

        throw $throwable;
    }

    /**
     * @param string $class
     * @param mixed ...$args
     */
    protected function dispatchEvent(string $class, ...$args): void
    {
        if ($this->events->hasListeners($class)) {
            $this->events->dispatch(new $class($this,...$args));
        }
    }
}
