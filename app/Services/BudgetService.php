<?php

namespace App\Services;

use App\Enums\BudgetPeriod;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetService
{
    public function getAll(User $user): LengthAwarePaginator
    {
        $budgets = $user->budgets()
            ->with('category')
            ->latest()
            ->paginate(request('per_page', 20));

        if ($budgets->isEmpty()) {
            return $budgets;
        }

        // Pre-fetch monthly and yearly spent totals in 2 batch queries to eliminate N+1 queries
        $monthlyStart = Carbon::now()->startOfMonth();
        $monthlyEnd   = Carbon::now()->endOfMonth();
        $yearlyStart  = Carbon::now()->startOfYear();
        $yearlyEnd    = Carbon::now()->endOfYear();

        $monthlySpent = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('date', [$monthlyStart, $monthlyEnd])
            ->selectRaw('category_id, currency_code, SUM(amount) as total')
            ->groupBy('category_id', 'currency_code')
            ->get()
            ->keyBy(fn ($row) => $row->getAttribute('category_id').'_'.$row->getAttribute('currency_code'));

        $yearlySpent = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('date', [$yearlyStart, $yearlyEnd])
            ->selectRaw('category_id, currency_code, SUM(amount) as total')
            ->groupBy('category_id', 'currency_code')
            ->get()
            ->keyBy(fn ($row) => $row->getAttribute('category_id').'_'.$row->getAttribute('currency_code'));

        $budgets->getCollection()->transform(function (\Illuminate\Database\Eloquent\Model $budget) use ($monthlySpent, $yearlySpent) {
            if ($budget instanceof Budget) {
                $key = $budget->category_id.'_'.$budget->currency_code;
                $map = $budget->period === BudgetPeriod::Monthly ? $monthlySpent : $yearlySpent;
                $spent = isset($map[$key]) ? (float) $map[$key]->getAttribute('total') : 0.0;
                $budget->setAttribute('spent', $spent);
            }

            return $budget;
        });

        return $budgets;
    }

    public function show(Budget $budget): Budget
    {
        $budget->load('category');
        $budget->setAttribute('spent', $this->calculateSpent($budget));

        return $budget;
    }

    public function create(User $user, array $data): Budget
    {
        $budget = $user->budgets()->create($data);
        if ($budget instanceof Budget) {
            return $budget;
        }
        throw new \UnexpectedValueException('Expected Budget model');
    }

    public function update(Budget $budget, array $data): Budget
    {
        $budget->update($data);

        return $budget;
    }

    public function delete(Budget $budget): void
    {
        $budget->delete();
    }

    /**
     * Calculate how much has been spent against a budget for the current period.
     *
     * monthly → current calendar month (1st to last day of current month)
     * yearly  → current calendar year  (Jan 1 to Dec 31)
     */
    private function calculateSpent(Budget $budget): float
    {
        [$periodStart, $periodEnd] = $this->getPeriodBounds($budget->period);

        return (float) Transaction::where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', TransactionType::Expense->value)
            ->where('currency_code', $budget->currency_code)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('amount');
    }

    /**
     * Return [start, end] Carbon dates for the given budget period.
     *
     * @return array{Carbon, Carbon}
     */
    private function getPeriodBounds(BudgetPeriod $period): array
    {
        return match ($period) {
            BudgetPeriod::Monthly => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            BudgetPeriod::Yearly => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
        };
    }
}
