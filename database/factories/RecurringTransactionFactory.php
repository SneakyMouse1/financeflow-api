<?php

namespace Database\Factories;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    protected $model = RecurringTransaction::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'account_id'    => Account::factory(),
            'category_id'   => Category::factory(),
            'name'          => fake()->words(3, true),
            'type'          => fake()->randomElement([TransactionType::Income, TransactionType::Expense]),
            'amount'        => fake()->randomFloat(2, 5, 200),
            'currency_code' => 'EUR',
            'frequency'     => fake()->randomElement(RecurringFrequency::cases()),
            'next_run_at'   => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'comment'       => fake()->sentence(),
            'is_active'     => true,
        ];
    }
}
