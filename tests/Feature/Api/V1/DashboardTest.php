<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_correct_structure_and_balances()
    {
        $user = User::factory()->create();

        // 1. Active account with balance
        Account::factory()->create([
            'user_id'     => $user->id,
            'balance'     => 1500.00,
            'is_archived' => false,
        ]);

        // 2. Archived account (should be ignored in total_balance)
        Account::factory()->create([
            'user_id'     => $user->id,
            'balance'     => 300.00,
            'is_archived' => true,
        ]);

        // 3. Transactions for current month income/expense
        $account = Account::first();
        Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
            'type'       => 'income',
            'amount'     => 200.00,
            'date'       => now()->format('Y-m-d'),
        ]);

        Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
            'type'       => 'expense',
            'amount'     => 50.00,
            'date'       => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'summary' => [
                    'total_balance' => 1500.00, // Ignoring archived
                    'month_income'  => 200.00,
                    'month_expense' => 50.00,
                    'month_savings' => 150.00,
                ],
            ],
        ]);
        $response->assertJsonCount(2, 'data.recent_transactions');
    }
}
