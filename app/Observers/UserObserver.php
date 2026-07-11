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
            ['name' => 'Food',            'type' => TransactionType::Expense, 'color' => '#ef4444', 'icon' => 'utensils'],
            ['name' => 'Transport',       'type' => TransactionType::Expense, 'color' => '#f97316', 'icon' => 'car'],
            ['name' => 'Bar',             'type' => TransactionType::Expense, 'color' => '#ec4899', 'icon' => 'glass-martini-alt'],
            ['name' => 'Cosmetics',       'type' => TransactionType::Expense, 'color' => '#d946ef', 'icon' => 'spa'],
            ['name' => 'Housing & Rent',  'type' => TransactionType::Expense, 'color' => '#8b5cf6', 'icon' => 'home'],
            ['name' => 'Healthcare',      'type' => TransactionType::Expense, 'color' => '#f43f5e', 'icon' => 'heart-pulse'],
            ['name' => 'Entertainment',   'type' => TransactionType::Expense, 'color' => '#06b6d4', 'icon' => 'film'],
            ['name' => 'Shopping',        'type' => TransactionType::Expense, 'color' => '#84cc16', 'icon' => 'shopping-bag'],
            ['name' => 'Education',       'type' => TransactionType::Expense, 'color' => '#f59e0b', 'icon' => 'book'],
            ['name' => 'Utilities',       'type' => TransactionType::Expense, 'color' => '#eab308', 'icon' => 'bolt'],
            ['name' => 'Travel',          'type' => TransactionType::Expense, 'color' => '#3b82f6', 'icon' => 'plane'],
            ['name' => 'Groceries',       'type' => TransactionType::Expense, 'color' => '#10b981', 'icon' => 'shopping-cart'],
            ['name' => 'Subscriptions',   'type' => TransactionType::Expense, 'color' => '#6366f1', 'icon' => 'tv'],
            ['name' => 'Services',        'type' => TransactionType::Expense, 'color' => '#6b7280', 'icon' => 'cogs'],
            ['name' => 'Gifts',           'type' => TransactionType::Expense, 'color' => '#a855f7', 'icon' => 'gift'],
            ['name' => 'Pets',            'type' => TransactionType::Expense, 'color' => '#14b8a6', 'icon' => 'paw'],
            // Income categories
            ['name' => 'Salary',          'type' => TransactionType::Income,  'color' => '#22c55e', 'icon' => 'briefcase'],
            ['name' => 'Freelance',       'type' => TransactionType::Income,  'color' => '#10b981', 'icon' => 'laptop'],
            ['name' => 'Investments',     'type' => TransactionType::Income,  'color' => '#3b82f6', 'icon' => 'trending-up'],
            ['name' => 'Other Income',    'type' => TransactionType::Income,  'color' => '#06b6d4', 'icon' => 'wallet'],
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
