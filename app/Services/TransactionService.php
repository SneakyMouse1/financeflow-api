<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\GoalDeposit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        return $user->transactions()
            ->with(['account', 'category', 'tags', 'relatedTransaction.account'])
            ->when($filters['account_id'] ?? null, fn ($q, $v) => $q->where('account_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['type'] ?? null, function ($q, $v) {
                if ($v === 'transfer') {
                    return $q->whereNotNull('transfer_id');
                }
                return $q->where('type', $v)->whereNull('transfer_id');
            })
            ->when($filters['currency_code'] ?? null, fn ($q, $v) => $q->where('currency_code', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $searchTerm = strtolower($v);
                return $q->where(function ($query) use ($searchTerm) {
                    $query->where(DB::raw('lower(comment)'), 'like', "%{$searchTerm}%")
                        ->orWhereHas('tags', fn ($q) => $q->where(DB::raw('lower(name)'), 'like', "%{$searchTerm}%"))
                        ->orWhereHas('category', fn ($q) => $q->where(DB::raw('lower(name)'), 'like', "%{$searchTerm}%"));
                });
            })
            ->when($filters['tag'] ?? null, fn ($q, $v) => $q->whereHas('tags', fn ($q) => $q->where('name', $v)))
            ->when($filters['sort'] ?? null, function ($q, $v) {
                // '-amount' means descending, 'amount' means ascending
                $direction = str_starts_with($v, '-') ? 'desc' : 'asc';
                $column = ltrim($v, '-');

                return $q->orderBy($column, $direction);
            }, fn ($q) => $q->latest('date'))
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(User $user, array $data): Transaction
    {
        // DB::transaction() ensures that if any step fails,
        // all database changes are rolled back automatically.
        $result = DB::transaction(function () use ($user, $data) {
            if ($data['type'] === TransactionType::Transfer->value) {
                return $this->createTransfer($user, $data);
            }

            $transaction = $user->transactions()->create($data);
            if (! $transaction instanceof Transaction) {
                throw new \UnexpectedValueException('Expected Transaction model');
            }
            $this->updateAccountBalance($transaction->account, $data['type'], $data['amount']);

            if (!empty($data['tags'])) {
                $transaction->tags()->sync($data['tags']);
            }

            return $transaction;
        });

        DashboardService::clearCache($user);

        return $result;
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        // Transfer transactions cannot be edited individually.
        // A transfer is an atomic pair — edit by deleting and recreating.
        if ($transaction->transfer_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => ['Transfer transactions cannot be edited. Delete and recreate the transfer instead.'],
            ]);
        }

        // Changing a regular transaction's type to 'transfer' is forbidden —
        // transfers require paired records and special balance handling.
        if (($data['type'] ?? null) === TransactionType::Transfer->value) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => ['Cannot change transaction type to transfer. Create a new transfer instead.'],
            ]);
        }

        $result = DB::transaction(function () use ($transaction, $data) {
            // Revert the old balance effect before applying the new one
            $this->revertAccountBalance($transaction->account, $transaction->type->value, $transaction->amount);

            // Capture the old amount before update to compute delta for goal deposits
            $oldAmount = $transaction->amount;

            $transaction->update($data);

            // Reload model from DB — clears cached relations (especially 'account').
            // Without this, if account_id changed, $transaction->account still
            // returns the OLD account and balance update goes to the wrong place.
            $transaction->refresh();

            $this->updateAccountBalance($transaction->account, $transaction->type->value, $transaction->amount);

            if (isset($data['tags'])) {
                $transaction->tags()->sync($data['tags']);
            }

            // If this transaction is linked to a goal deposit, sync the deposit
            // amount and goal's current_amount so they stay consistent.
            if (isset($data['amount'])) {
                $goalDeposit = GoalDeposit::where('transaction_id', $transaction->id)->first();
                if ($goalDeposit) {
                    $delta = $transaction->amount - $oldAmount;
                    $goalDeposit->update(['amount' => $transaction->amount]);

                    $goal = $goalDeposit->goal;
                    if ($delta > 0) {
                        $goal->increment('current_amount', $delta);
                    } elseif ($delta < 0) {
                        $goal->decrement('current_amount', abs($delta));
                    }

                    // Re-evaluate goal completion status after amount change
                    $goal->refresh();
                    if ($goal->current_amount >= $goal->target_amount && $goal->status->value !== 'completed') {
                        $goal->update(['status' => GoalStatus::Completed->value]);
                    } elseif ($goal->current_amount < $goal->target_amount && $goal->status->value === 'completed') {
                        $goal->update(['status' => GoalStatus::Active->value]);
                    }
                }
            }

            return $transaction;
        });

        DashboardService::clearCache($transaction->user_id);

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Transaction $transaction): void
    {
        $userId = $transaction->user_id;

        DB::transaction(function () use ($transaction) {
            $this->revertAccountBalance($transaction->account, $transaction->type->value, $transaction->amount);

            // If the transaction was created from a goal deposit, revert and delete the deposit
            $goalDeposit = GoalDeposit::where('transaction_id', $transaction->id)->first();
            if ($goalDeposit) {
                $goal = $goalDeposit->goal;
                $goal->decrement('current_amount', $goalDeposit->amount);
                
                if ($goal->current_amount < $goal->target_amount && $goal->status->value === 'completed') {
                    $goal->update(['status' => GoalStatus::Active->value]);
                }
                
                $goalDeposit->delete();
            }

            // If this is a transfer, delete the related transaction too
            if ($transaction->transfer_id && $transaction->relatedTransaction) {
                $this->revertAccountBalance(
                    $transaction->relatedTransaction->account,
                    $transaction->relatedTransaction->type->value,
                    $transaction->relatedTransaction->amount
                );
                // Clean up attachments on the related transaction before deleting it
                $this->deleteAttachments($transaction->relatedTransaction);
                $transaction->relatedTransaction->delete();
            }

            // Clean up attachments before soft-deleting the transaction
            $this->deleteAttachments($transaction);

            $transaction->delete();
        });

        DashboardService::clearCache($userId);
    }

    private function createTransfer(User $user, array $data): Transaction
    {
        $transferId = Str::uuid();

        $from = $user->transactions()->create([
            'account_id' => $data['account_id'],
            'type' => TransactionType::Expense->value,
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'],
            'date' => $data['date'],
            'comment' => $data['comment'] ?? null,
            'transfer_id' => $transferId,
            'category_id' => null,
        ]);

        $to = $user->transactions()->create([
            'account_id' => $data['to_account_id'],
            'type' => TransactionType::Income->value,
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'],
            'date' => $data['date'],
            'comment' => $data['comment'] ?? null,
            'transfer_id' => $transferId,
            'category_id' => null,
        ]);

        if (! $from instanceof Transaction || ! $to instanceof Transaction) {
            throw new \UnexpectedValueException('Expected Transaction model');
        }

        $fromAccount = Account::find($data['account_id']);
        $toAccount = Account::find($data['to_account_id']);
        if (! $fromAccount instanceof Account || ! $toAccount instanceof Account) {
            throw new \UnexpectedValueException('Expected Account model');
        }

        // Link both transactions to each other
        $from->update(['related_transaction_id' => $to->id]);
        $to->update(['related_transaction_id' => $from->id]);

        $this->updateAccountBalance(
            $fromAccount,
            TransactionType::Expense->value,
            $data['amount']
        );

        $this->updateAccountBalance(
            $toAccount,
            TransactionType::Income->value,
            $data['amount']
        );

        return $from;
    }

    private function updateAccountBalance(Account $account, string $type, float $amount): void
    {
        if ($type === TransactionType::Income->value) {
            $account->increment('balance', $amount);
        } else {
            $account->decrement('balance', $amount);
        }
    }

    private function revertAccountBalance(Account $account, string $type, float $amount): void
    {
        // Revert is the opposite of the original operation
        if ($type === TransactionType::Income->value) {
            $account->decrement('balance', $amount);
        } else {
            $account->increment('balance', $amount);
        }
    }

    /**
     * Delete all attachment files from disk and remove DB records.
     *
     * Must be called before soft-deleting a transaction, because after
     * soft delete AttachmentPolicy can no longer authorize deletion
     * (it checks $attachment->transaction which returns null due to SoftDeletes).
     */
    private function deleteAttachments(Transaction $transaction): void
    {
        $transaction->attachments->each(function (\Illuminate\Database\Eloquent\Model $attachment) {
            if ($attachment instanceof Attachment) {
                Storage::disk('public')->delete($attachment->path);
                $attachment->delete();
            }
        });
    }
}
