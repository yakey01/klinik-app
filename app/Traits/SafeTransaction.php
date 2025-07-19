<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Throwable;

trait SafeTransaction
{
    /**
     * Execute a callback within a database transaction safely.
     * Only starts a new transaction if one is not already active.
     */
    protected function safeTransaction(callable $callback)
    {
        // If we're already in a transaction (e.g., during testing with RefreshDatabase),
        // just execute the callback without starting a new transaction
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        // Otherwise, start a new transaction
        return DB::transaction($callback);
    }

    /**
     * Begin a transaction only if one is not already active.
     */
    protected function safeBeginTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            DB::beginTransaction();
        }
    }

    /**
     * Commit a transaction only if we started it.
     */
    protected function safeCommit(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::commit();
        }
    }

    /**
     * Rollback a transaction only if one is active.
     */
    protected function safeRollback(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollback();
        }
    }

    /**
     * Check if we're currently in a testing environment with RefreshDatabase.
     */
    protected function isInTestTransaction(): bool
    {
        return app()->environment('testing') && DB::transactionLevel() > 0;
    }
}