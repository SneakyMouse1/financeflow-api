<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'name'           => fake()->words(2, true),
            'target_amount'  => fake()->randomFloat(2, 500, 10000),
            'current_amount' => 0.0,
            'currency_code'  => 'EUR',
            'status'         => GoalStatus::Active,
            'deadline'       => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
        ];
    }
}
