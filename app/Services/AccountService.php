<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function getAll(User $user): Collection
    {
        $query = Account::where('user_id', $user->id);
        if (request()->boolean('archived')) {
            $query->withTrashed();
        }
        return $query->get();
    }

    public function create(User $user, array $data): Account
    {
        $account = $user->accounts()->create($data);
        if ($account instanceof Account) {
            return $account;
        }
        throw new \UnexpectedValueException('Expected Account model');
    }

    public function update(Account $account, array $data): Account
    {
        $account->update($data);

        return $account;
    }

    public function delete(Account $account): void
    {
        DB::transaction(function () use ($account) {
            // cascadeOnDelete() only fires on hard delete, so we handle soft-delete cascade manually.
            // By deleting through TransactionService, we ensure that transfer pairs on other accounts
            // are also deleted and their balances reverted properly.
            $transactionService = app(TransactionService::class);
            $account->transactions()->get()->each(function (\Illuminate\Database\Eloquent\Model $transaction) use ($transactionService) {
                if ($transaction instanceof Transaction) {
                    $transactionService->delete($transaction);
                }
            });

            // Deactivate recurring transactions that reference this account.
            // Without this, the daily scheduler would try to create transactions
            // on a soft-deleted account, causing a fatal error that blocks all users.
            $account->recurringTransactions()->update(['is_active' => false]);

            $account->delete();
        });
    }
}
