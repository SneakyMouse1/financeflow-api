<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(User $user): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $thirtyDaysAgo = $now->copy()->subDays(30)->startOfDay();

        return [
            'summary' => $this->getSummary($user, $startOfMonth, $endOfMonth),
            'recent_transactions' => $this->getRecentTransactions($user),
            'chart' => $this->getChartData($user, $thirtyDaysAgo, $now),
            'top_categories' => $this->getTopCategories($user, $startOfMonth, $endOfMonth),
        ];
    }

    /**
     * Total balance + monthly income/expenses/savings.
     */
    private function getSummary(User $user, Carbon $from, Carbon $to): array
    {
        // Total balance across all non-archived, non-deleted accounts (legacy)
        $totalBalance = $user->accounts()
            ->where('is_archived', false)
            ->sum('balance');

        // Detailed balances grouped by currency to prevent currency mismatch errors
        $balances = $user->accounts()
            ->where('is_archived', false)
            ->selectRaw('currency_code, SUM(balance) as total')
            ->groupBy('currency_code')
            ->get()
            ->map(fn ($row) => [
                'currency_code' => $row->getAttribute('currency_code'),
                'total' => (float) $row->getAttribute('total'),
            ])
            ->values()
            ->all();

        // Monthly aggregates
        $monthly = $user->transactions()
            ->whereBetween('date', [$from, $to])
            ->whereIn('type', ['income', 'expense'])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();

        $income = 0.0;
        $expenses = 0.0;

        foreach ($monthly as $row) {
            $typeVal = $row->getAttribute('type');
            // Use the enum value for comparison
            $type = $typeVal instanceof TransactionType ? $typeVal->value : (string) $typeVal;
            if ($type === 'income') {
                $income = (float) $row->getAttribute('total');
            } elseif ($type === 'expense') {
                $expenses = (float) $row->getAttribute('total');
            }
        }

        return [
            'total_balance' => (float) $totalBalance,
            'balances' => $balances,
            'month_income' => $income,
            'month_expense' => $expenses,
            'month_savings' => round($income - $expenses, 2),
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    /**
     * Last 5 transactions with relations.
     */
    private function getRecentTransactions(User $user): array
    {
        return $user->transactions()
            ->with(['account', 'category'])
            ->latest('date')
            ->limit(5)
            ->get()
            ->map(function (Model $t) {
                $type = 'expense';
                $comment = null;
                $accountName = null;
                $categoryName = null;
                $dateString = '';
                $amount = 0.0;
                $currencyCode = '';
                if ($t instanceof Transaction) {
                    $type = $t->type->value;
                    $comment = $t->comment;
                    $accountName = $t->account?->name;
                    $categoryName = $t->category?->name;
                    $dateString = $t->date->toDateString();
                    $amount = (float) $t->amount;
                    $currencyCode = $t->currency_code;
                }

                return [
                    'id' => $t->getKey(),
                    'type' => $type,
                    'amount' => $amount,
                    'currency_code' => $currencyCode,
                    'date' => $dateString,
                    'comment' => $comment,
                    'account' => $accountName,
                    'category' => $categoryName,
                ];
            })
            ->all();
    }

    /**
     * Daily income/expense totals for the last 30 days for charts.
     */
    private function getChartData(User $user, Carbon $from, Carbon $to): array
    {
        $rows = $user->transactions()
            ->whereBetween('date', [$from, $to])
            ->whereIn('type', ['income', 'expense'])
            ->select('date', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        // Build a map: date -> {income, expense}
        $chart = [];
        foreach ($rows as $row) {
            $dateVal = $row->getAttribute('date');
            $date = ($dateVal instanceof Carbon) ? $dateVal->toDateString() : (string) $dateVal;
            if (! isset($chart[$date])) {
                $chart[$date] = ['date' => $date, 'income' => 0.0, 'expense' => 0.0];
            }
            $typeVal = $row->getAttribute('type');
            $type = $typeVal instanceof TransactionType ? $typeVal->value : (string) $typeVal;
            $chart[$date][$type] = (float) $row->getAttribute('total');
        }

        return array_values($chart);
    }

    /**
     * Top 5 expense categories this month by total amount.
     */
    private function getTopCategories(User $user, Carbon $from, Carbon $to): array
    {
        return $user->transactions()
            ->with('category')
            ->whereBetween('date', [$from, $to])
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function (Model $t) {
                $categoryName = null;
                if ($t instanceof Transaction) {
                    $categoryName = $t->category?->name;
                }

                return [
                    'category' => $categoryName,
                    'total' => (float) $t->getAttribute('total'),
                ];
            })
            ->all();
    }
}
