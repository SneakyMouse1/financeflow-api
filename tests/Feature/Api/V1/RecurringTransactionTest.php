<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_only_own_recurring_transactions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = Account::factory()->create(['user_id' => $user1->id]);
        $account2 = Account::factory()->create(['user_id' => $user2->id]);

        RecurringTransaction::factory()->create([
            'user_id'    => $user1->id,
            'account_id' => $account1->id,
            'name'       => 'Netflix User 1',
        ]);

        RecurringTransaction::factory()->create([
            'user_id'    => $user2->id,
            'account_id' => $account2->id,
            'name'       => 'Spotify User 2',
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/recurring-transactions');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Netflix User 1');
    }

    public function test_can_create_recurring_transaction()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/recurring-transactions', [
                'name'          => 'Netflix',
                'account_id'    => $account->id,
                'category_id'   => $category->id,
                'type'          => 'expense',
                'amount'        => 14.99,
                'currency_code' => 'EUR',
                'frequency'     => 'monthly',
                'next_run_at'   => '2026-07-10',
                'comment'       => 'Subscription fee',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $user->id,
            'name'    => 'Netflix',
            'amount'  => 14.99,
        ]);
    }

    public function test_cannot_create_recurring_transaction_with_someone_elses_account()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $accountOfUser2 = Account::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->postJson('/api/v1/recurring-transactions', [
                'name'          => 'Invalid Account',
                'account_id'    => $accountOfUser2->id,
                'type'          => 'expense',
                'amount'        => 10.00,
                'currency_code' => 'EUR',
                'frequency'     => 'weekly',
                'next_run_at'   => '2026-07-10',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['account_id']);
    }

    public function test_cannot_create_recurring_transaction_with_transfer_type()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/recurring-transactions', [
                'name'          => 'Invalid Type',
                'account_id'    => $account->id,
                'type'          => 'transfer',
                'amount'        => 100.00,
                'currency_code' => 'EUR',
                'frequency'     => 'monthly',
                'next_run_at'   => '2026-07-10',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_can_update_recurring_transaction()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $recurring = RecurringTransaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
            'name'       => 'Old Name',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/recurring-transactions/{$recurring->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('New Name', $recurring->fresh()->name);
    }

    public function test_deleting_recurring_transaction_returns_empty_204()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $recurring = RecurringTransaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/recurring-transactions/{$recurring->id}");

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        $this->assertSoftDeleted('recurring_transactions', ['id' => $recurring->id]);
    }

    public function test_cannot_view_or_modify_someone_elses_recurring_transaction()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account2 = Account::factory()->create(['user_id' => $user2->id]);
        $recurring = RecurringTransaction::factory()->create([
            'user_id'    => $user2->id,
            'account_id' => $account2->id,
        ]);

        $this->actingAs($user1)
            ->getJson("/api/v1/recurring-transactions/{$recurring->id}")
            ->assertStatus(403);

        $this->actingAs($user1)
            ->patchJson("/api/v1/recurring-transactions/{$recurring->id}", ['name' => 'Hack'])
            ->assertStatus(403);

        $this->actingAs($user1)
            ->deleteJson("/api/v1/recurring-transactions/{$recurring->id}")
            ->assertStatus(403);
    }

    public function test_scheduler_processes_active_recurring_transactions_and_advances_next_run_at()
    {
        // Fix test datetime context
        Carbon::setTestNow('2026-07-08 10:00:00');

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 100.00]);
        $category = Category::factory()->create(['user_id' => $user->id]);

        $recurring = RecurringTransaction::factory()->create([
            'user_id'       => $user->id,
            'account_id'    => $account->id,
            'category_id'   => $category->id,
            'type'          => 'expense',
            'amount'        => 15.00,
            'currency_code' => 'EUR',
            'frequency'     => 'monthly',
            'next_run_at'   => '2026-07-08', // Due today
            'is_active'     => true,
        ]);

        // Run scheduler
        $this->runRecurringTransactionsScheduler();

        // Check transaction was created and balance updated
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals($account->id, $transaction->account_id);
        $this->assertEquals('expense', $transaction->type->value);
        $this->assertEquals(15.00, $transaction->amount);
        $this->assertEquals('2026-07-08', $transaction->date->toDateString());
        $this->assertEquals(85.00, $account->fresh()->balance);

        // Check next_run_at advanced to next month (August 8, 2026)
        $this->assertEquals('2026-08-08', $recurring->fresh()->next_run_at->toDateString());

        Carbon::setTestNow(); // Reset time mock
    }

    public function test_scheduler_ignores_inactive_or_future_recurring_transactions()
    {
        Carbon::setTestNow('2026-07-08 10:00:00');

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 100.00]);

        // 1. Future recurring transaction
        $future = RecurringTransaction::factory()->create([
            'user_id'       => $user->id,
            'account_id'    => $account->id,
            'type'          => 'expense',
            'amount'        => 10.00,
            'next_run_at'   => '2026-07-09', // Due tomorrow
            'is_active'     => true,
        ]);

        // 2. Inactive recurring transaction
        $inactive = RecurringTransaction::factory()->create([
            'user_id'       => $user->id,
            'account_id'    => $account->id,
            'type'          => 'expense',
            'amount'       => 20.00,
            'next_run_at'   => '2026-07-08', // Due today but inactive
            'is_active'     => false,
        ]);

        $this->runRecurringTransactionsScheduler();

        // No transactions should be created
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
        ]);
        $this->assertEquals(100.00, $account->fresh()->balance);

        // next_run_at should remain unchanged
        $this->assertEquals('2026-07-09', $future->fresh()->next_run_at->toDateString());
        $this->assertEquals('2026-07-08', $inactive->fresh()->next_run_at->toDateString());

        Carbon::setTestNow();
    }

    public function test_scheduler_handles_exceptions_without_stopping_other_jobs()
    {
        Carbon::setTestNow('2026-07-08 10:00:00');

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 100.00]);

        // 1. Broken recurring transaction (missing account relationship or invalid balance type triggering exception)
        // Here, we can create a record that points to a non-existent account ID but passes DB integrity checks if constraints permit.
        // Or simpler: point it to an account, but then force delete the account so it's missing in DB, triggering NPE in scheduler.
        $brokenAccount = Account::factory()->create(['user_id' => $user->id]);
        $broken = RecurringTransaction::factory()->create([
            'user_id'       => $user->id,
            'account_id'    => $brokenAccount->id,
            'type'          => 'expense',
            'amount'        => 10.00,
            'next_run_at'   => '2026-07-08',
            'is_active'     => true,
        ]);
        // Soft-delete the account to ensure transaction service throws an exception
        // (NPE on null account in updateAccountBalance) without cascading delete in DB.
        $brokenAccount->delete();

        // 2. A healthy recurring transaction that comes after the broken one
        $healthyAccount = Account::factory()->create(['user_id' => $user->id, 'balance' => 200.00]);
        $healthy = RecurringTransaction::factory()->create([
            'user_id'       => $user->id,
            'account_id'    => $healthyAccount->id,
            'type'          => 'expense',
            'amount'        => 50.00,
            'frequency'     => 'monthly',
            'next_run_at'   => '2026-07-08',
            'is_active'     => true,
        ]);

        // Expect log entry
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to process recurring transaction', \Mockery::on(function ($data) use ($broken) {
                return $data['recurring_transaction_id'] === $broken->id;
            }));

        $this->runRecurringTransactionsScheduler();

        // The healthy transaction should still have been processed successfully!
        $transaction = Transaction::where('account_id', $healthyAccount->id)->firstOrFail();
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals(50.00, $transaction->amount);
        $this->assertEquals(150.00, $healthyAccount->fresh()->balance);
        $this->assertEquals('2026-08-08', $healthy->fresh()->next_run_at->toDateString());

        Carbon::setTestNow();
    }

    protected function runRecurringTransactionsScheduler()
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $event = collect($schedule->events())->first(function ($event) {
            return str_contains($event->description, 'process-recurring-transactions');
        });

        if ($event) {
            $event->run(app());
        } else {
            $this->fail('process-recurring-transactions schedule task not found.');
        }
    }
}
