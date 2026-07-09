<?php

namespace App\Observers;

use App\Enums\TransactionType;
use App\Models\User;

class UserObserver
{
    /**
     * Create a standard set of default categories when a new user registers.
     *
     * These categories are marked with is_default = true so that:
     *  - The UI can display them as system-provided (non-deletable via CategoryPolicy).
     *  - They act as a helpful starting point for budgets and transactions.
     */
    public function created(User $user): void
    {
        $defaults = [
            // Expense categories
            ['name' => 'Food & Dining',   'type' => TransactionType::Expense, 'color' => '#ef4444', 'icon' => 'utensils'],
            ['name' => 'Transportation',  'type' => TransactionType::Expense, 'color' => '#f97316', 'icon' => 'car'],
            ['name' => 'Housing & Rent',  'type' => TransactionType::Expense, 'color' => '#8b5cf6', 'icon' => 'home'],
            ['name' => 'Healthcare',      'type' => TransactionType::Expense, 'color' => '#ec4899', 'icon' => 'heart-pulse'],
            ['name' => 'Entertainment',   'type' => TransactionType::Expense, 'color' => '#06b6d4', 'icon' => 'film'],
            ['name' => 'Shopping',        'type' => TransactionType::Expense, 'color' => '#84cc16', 'icon' => 'shopping-bag'],
            ['name' => 'Education',       'type' => TransactionType::Expense, 'color' => '#f59e0b', 'icon' => 'book'],
            // Income categories
            ['name' => 'Salary',          'type' => TransactionType::Income,  'color' => '#22c55e', 'icon' => 'briefcase'],
            ['name' => 'Freelance',       'type' => TransactionType::Income,  'color' => '#10b981', 'icon' => 'laptop'],
            ['name' => 'Investments',     'type' => TransactionType::Income,  'color' => '#3b82f6', 'icon' => 'trending-up'],
        ];

        foreach ($defaults as $category) {
            $user->categories()->create([
                'name'       => $category['name'],
                'type'       => $category['type'],
                'color'      => $category['color'],
                'icon'       => $category['icon'],
                'is_default' => true,
            ]);
        }
    }
}
