<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\TransactionType;
use App\Models\Goal;
use App\Models\GoalDeposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function getAll(User $user): LengthAwarePaginator
    {
        return $user->goals()
            ->with('deposits')
            ->latest()
            ->paginate(request('per_page', 20));
    }

    public function create(User $user, array $data): Goal
    {
        $goal = $user->goals()->create($data);
        if ($goal instanceof Goal) {
            return $goal;
        }
        throw new \UnexpectedValueException('Expected Goal model');
    }

    public function update(Goal $goal, array $data): Goal
    {
        $goal->update($data);

        return $goal->fresh();
    }

    public function delete(Goal $goal): void
    {
        DB::transaction(function () use ($goal) {
            // Delete all associated transactions to restore account balances
            $transactionService = app(TransactionService::class);
            $goal->deposits()->get()->each(function (\Illuminate\Database\Eloquent\Model $deposit) use ($transactionService) {
                if ($deposit instanceof GoalDeposit && $deposit->transaction_id) {
                    $transaction = Transaction::find($deposit->transaction_id);
                    if ($transaction) {
                        $transactionService->delete($transaction);
                    }
                }
            });

            $goal->delete();
        });
    }

    public function deposit(Goal $goal, array $data): Goal
    {
        return DB::transaction(function () use ($goal, $data) {
            $transactionId = null;

            // If account_id is provided, create a real transaction to deduct funds from the account
            if (!empty($data['account_id'])) {
                $transactionService = app(TransactionService::class);
                $transaction = $transactionService->create($goal->user, [
                    'account_id'    => $data['account_id'],
                    'type'          => TransactionType::Expense->value,
                    'amount'        => $data['amount'],
                    'currency_code' => $goal->currency_code,
                    'date'          => now()->toDateString(),
                    'comment'       => $data['comment'] ?? "Deposit to goal: {$goal->name}",
                ]);
                $transactionId = $transaction->id;
            }

            $goal->deposits()->create([
                'amount'         => $data['amount'],
                'comment'        => $data['comment'] ?? null,
                'transaction_id' => $transactionId,
            ]);

            $goal->increment('current_amount', $data['amount']);

            // Automatically mark goal as completed when target is reached
            if ($goal->current_amount >= $goal->target_amount) {
                $goal->update(['status' => GoalStatus::Completed->value]);
            }

            return $goal->fresh();
        });
    }
}
