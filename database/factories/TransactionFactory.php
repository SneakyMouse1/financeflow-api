<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'account_id'    => Account::factory(),
            'category_id'   => Category::factory(),
            'type'          => fake()->randomElement(TransactionType::cases()),
            'amount'        => fake()->randomFloat(2, 10, 1000),
            'currency_code' => 'EUR',
            'date'          => fake()->date(),
            'comment'       => fake()->sentence(),
            'transfer_id'   => null,
        ];
    }
}
