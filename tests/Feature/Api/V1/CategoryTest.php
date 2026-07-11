<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Number of default categories created by UserObserver for every new user.
     */
    private const DEFAULT_CATEGORY_COUNT = 20;

    public function test_registration_creates_default_categories()
    {
        $user = User::factory()->create();

        $this->assertEquals(
            self::DEFAULT_CATEGORY_COUNT,
            $user->categories()->where('is_default', true)->count()
        );
    }

    public function test_can_list_only_own_categories()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Each user gets DEFAULT_CATEGORY_COUNT default categories from UserObserver.
        // We add one extra custom category to user1.
        Category::factory()->create(['user_id' => $user1->id, 'name' => 'Food of User 1']);
        Category::factory()->create(['user_id' => $user2->id, 'name' => 'Rent of User 2']);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/categories');

        $response->assertStatus(200);
        // user1 has DEFAULT_CATEGORY_COUNT defaults + 1 custom = DEFAULT_CATEGORY_COUNT + 1
        $response->assertJsonCount(self::DEFAULT_CATEGORY_COUNT + 1, 'data');
    }

    public function test_can_create_category()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/categories', [
                'name'  => 'Entertainment',
                'type'  => 'expense',
                'color' => '#10b981',
                'icon'  => 'film',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name'    => 'Entertainment',
        ]);
    }

    public function test_can_update_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/categories/{$category->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('New Name', $category->fresh()->name);
    }

    public function test_cannot_delete_default_category()
    {
        $user = User::factory()->create();
        $defaultCategory = Category::factory()->create([
            'user_id'    => $user->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/categories/{$defaultCategory->id}");

        // CategoryPolicy should return false for default categories
        $response->assertStatus(403);
        $this->assertDatabaseHas('categories', ['id' => $defaultCategory->id, 'deleted_at' => null]);
    }

    public function test_cannot_view_or_modify_someone_elses_category()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1)
            ->getJson("/api/v1/categories/{$category->id}")
            ->assertStatus(403);

        $this->actingAs($user1)
            ->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Hack'])
            ->assertStatus(403);

        $this->actingAs($user1)
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(403);
    }

    public function test_cannot_delete_category_with_active_budgets()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        
        // Create an active budget linked to this category
        \App\Models\Budget::factory()->create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);
        $this->assertDatabaseHas('categories', [
            'id'         => $category->id,
            'deleted_at' => null,
        ]);
    }
}
