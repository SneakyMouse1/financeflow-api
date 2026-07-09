<?php

namespace Tests\Feature\Api\V1;

use App\Enums\GoalStatus;
use App\Models\Account;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_goal()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/goals', [
                'name' => 'New Car',
                'target_amount' => 5000.00,
                'currency_code' => 'EUR',
                'status' => 'active',
                'deadline' => '2026-12-31',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'name' => 'New Car',
            'target_amount' => 5000.00,
        ]);
    }

    public function test_depositing_to_goal_increases_current_amount()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000.00,
            'current_amount' => 100.00,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/goals/{$goal->id}/deposit", [
                'amount' => 200.00,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(300.00, $goal->fresh()->current_amount);
        $this->assertDatabaseHas('goal_deposits', [
            'goal_id' => $goal->id,
            'amount' => 200.00,
        ]);
    }

    public function test_goal_completes_automatically_when_target_reached()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000.00,
            'current_amount' => 800.00,
            'status' => GoalStatus::Active,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/goals/{$goal->id}/deposit", [
                'amount' => 200.00,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1000.00, $goal->fresh()->current_amount);
        $this->assertEquals(GoalStatus::Completed, $goal->fresh()->status);
    }

    public function test_deleting_goal_returns_empty_204()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/goals/{$goal->id}");

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_can_update_goal_name_deadline_and_status()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'status' => GoalStatus::Active,
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/goals/{$goal->id}", [
                'name' => 'New Car Fund',
                'status' => 'paused',
                'deadline' => '2027-12-31',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Car Fund');
        $response->assertJsonPath('data.status', 'paused');
        $this->assertEquals('New Car Fund', $goal->fresh()->name);
    }

    public function test_cannot_set_target_amount_below_current_amount()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000.00,
            'current_amount' => 500.00,
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/goals/{$goal->id}", [
                'target_amount' => 100.00,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_amount']);
    }

    public function test_cannot_update_another_users_goal()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1)
            ->patchJson("/api/v1/goals/{$goal->id}", ['name' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_depositing_to_goal_with_account_id_creates_transaction_and_deducts_balance()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00,
        ]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 5000.00,
            'currency_code' => 'USD',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/goals/{$goal->id}/deposit", [
                'amount' => 300.00,
                'account_id' => $account->id,
                'comment' => 'Saving for laptop',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(700.00, $account->fresh()->balance);

        $deposit = $goal->deposits()->first();
        $this->assertNotNull($deposit->transaction_id);

        $this->assertDatabaseHas('transactions', [
            'id' => $deposit->transaction_id,
            'amount' => 300.00,
            'type' => 'expense',
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_deleting_goal_with_linked_deposits_removes_transactions_and_reverts_balances()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00,
        ]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 5000.00,
            'currency_code' => 'USD',
        ]);

        // Create a deposit that deducts 300 USD
        $this->actingAs($user)
            ->postJson("/api/v1/goals/{$goal->id}/deposit", [
                'amount' => 300.00,
                'account_id' => $account->id,
            ]);

        $this->assertEquals(700.00, $account->fresh()->balance);
        $deposit = $goal->deposits()->first();

        // Delete the goal
        $this->actingAs($user)
            ->deleteJson("/api/v1/goals/{$goal->id}")
            ->assertStatus(204);

        // Account balance should be reverted back to 1000.00
        $this->assertEquals(1000.00, $account->fresh()->balance);

        // Linked transaction should be soft-deleted
        $this->assertSoftDeleted('transactions', [
            'id' => $deposit->transaction_id,
        ]);
    }

    public function test_deleting_deposit_transaction_reverts_goal_progress()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00,
        ]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 5000.00,
            'current_amount' => 100.00,
            'currency_code' => 'USD',
        ]);

        // Create a deposit that deducts 300 USD
        $this->actingAs($user)
            ->postJson("/api/v1/goals/{$goal->id}/deposit", [
                'amount' => 300.00,
                'account_id' => $account->id,
            ]);

        $this->assertEquals(400.00, $goal->fresh()->current_amount);
        $deposit = $goal->deposits()->first();

        // Delete the transaction linked to the deposit
        $this->actingAs($user)
            ->deleteJson("/api/v1/transactions/{$deposit->transaction_id}")
            ->assertStatus(204);

        // Goal progress should revert to 100.00
        $this->assertEquals(100.00, $goal->fresh()->current_amount);

        // GoalDeposit record should be deleted
        $this->assertDatabaseMissing('goal_deposits', [
            'id' => $deposit->id,
        ]);
    }
}
