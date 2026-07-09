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

        // Attach spent amount to each budget.
        // Note: this executes N queries for N budgets (one per budget).
        // Acceptable for a personal-finance app where budgets are few.
        $budgets->getCollection()->transform(function (\Illuminate\Database\Eloquent\Model $budget) {
            if ($budget instanceof Budget) {
                $budget->setAttribute('spent', $this->calculateSpent($budget));
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
