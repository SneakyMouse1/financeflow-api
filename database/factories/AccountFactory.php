<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'name'          => fake()->words(2, true),
            'type'          => fake()->randomElement(AccountType::cases()),
            'balance'       => fake()->randomFloat(2, 100, 10000),
            'currency_code' => 'EUR',
            'color'         => fake()->hexColor(),
            'icon'          => 'credit-card',
            'is_archived'   => false,
        ];
    }
}
