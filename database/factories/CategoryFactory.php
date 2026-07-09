<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'name'       => fake()->word(),
            'type'       => fake()->randomElement(TransactionType::cases()),
            'color'      => fake()->hexColor(),
            'icon'       => 'shopping-cart',
            'is_default' => false,
        ];
    }
}
