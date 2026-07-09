<?php

namespace Tests\Feature\Api\V1;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_budget_with_own_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/budgets', [
                'category_id'   => $category->id,
                'period'        => 'monthly',
                'amount'        => 500.00,
                'currency_code' => 'EUR',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budgets', [
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'amount'      => 500.00,
        ]);
    }

    public function test_cannot_create_budget_with_someone_elses_category()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $categoryOfUser2 = Category::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->postJson('/api/v1/budgets', [
                'category_id'   => $categoryOfUser2->id,
                'period'        => 'monthly',
                'amount'        => 500.00,
                'currency_code' => 'EUR',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_can_update_budget()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create(['user_id' => $user->id, 'amount' => 100.00]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/budgets/{$budget->id}", [
                'amount' => 250.00,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(250.00, $budget->fresh()->amount);
    }

    public function test_cannot_update_budget_with_someone_elses_category()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $budget = Budget::factory()->create(['user_id' => $user1->id]);
        $categoryOfUser2 = Category::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->patchJson("/api/v1/budgets/{$budget->id}", [
                'category_id' => $categoryOfUser2->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_deleting_budget_returns_empty_204()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }
    public function test_budget_response_includes_spent_remaining_and_progress()
    {
        $user     = User::factory()->create();
        $budget   = Budget::factory()->create(['user_id' => $user->id, 'amount' => 500.00]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'spent',
                'remaining',
                'progress_percentage',
            ],
        ]);
        // No transactions yet — spent must be 0, remaining must equal amount
        $data = $response->json('data');
        $this->assertEquals(0, $data['spent']);
        $this->assertEquals(500.0, $data['remaining']);
        $this->assertEquals(0, $data['progress_percentage']);
    }

    public function test_budget_spent_calculates_only_expense_transactions_for_correct_category()
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $account  = \App\Models\Account::factory()->create(['user_id' => $user->id]);
        $budget   = Budget::factory()->create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'period'      => 'monthly',
            'amount'      => 1000.00,
        ]);

        // Create an expense transaction for this category this month
        \App\Models\Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'category_id' => $category->id,
            'type'        => 'expense',
            'amount'      => 300.00,
            'date'        => now()->toDateString(),
        ]);

        // Create an income transaction — should NOT count
        \App\Models\Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'category_id' => $category->id,
            'type'        => 'income',
            'amount'      => 200.00,
            'date'        => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(300.0, $data['spent']);
        $this->assertEquals(700.0, $data['remaining']);
        $this->assertEquals(30.0, $data['progress_percentage']);
    }
}
