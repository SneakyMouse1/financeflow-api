<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class RecurringTransactionService
{
    public function getAll(User $user): LengthAwarePaginator
    {
        return $user->recurringTransactions()
            ->with(['account', 'category'])
            ->latest()
            ->paginate(request('per_page', 20));
    }

    public function show(RecurringTransaction $recurringTransaction): RecurringTransaction
    {
        $recurringTransaction->load(['account', 'category']);

        return $recurringTransaction;
    }

    public function create(User $user, array $data): RecurringTransaction
    {
        $recurring = $user->recurringTransactions()->create($data);
        if ($recurring instanceof RecurringTransaction) {
            $recurring->load(['account', 'category']);
            return $recurring;
        }
        throw new \UnexpectedValueException('Expected RecurringTransaction model');
    }

    public function update(RecurringTransaction $recurringTransaction, array $data): RecurringTransaction
    {
        $recurringTransaction->update($data);
        $recurringTransaction->load(['account', 'category']);

        return $recurringTransaction;
    }

    public function delete(RecurringTransaction $recurringTransaction): void
    {
        $recurringTransaction->delete();
    }
}
