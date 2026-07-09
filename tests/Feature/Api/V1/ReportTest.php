<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_filtered_report()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category1 = Category::factory()->create(['user_id' => $user->id, 'name' => 'Food']);
        $category2 = Category::factory()->create(['user_id' => $user->id, 'name' => 'Salary']);

        // Create expense transaction
        Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'category_id' => $category1->id,
            'type'        => 'expense',
            'amount'      => 75.50,
            'date'        => '2026-07-03',
        ]);

        // Create income transaction
        Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'category_id' => $category2->id,
            'type'        => 'income',
            'amount'      => 1000.00,
            'date'        => '2026-07-03',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/reports?date_from=2026-07-01&date_to=2026-07-31');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'summary' => [
                    'income'  => 1000.00,
                    'expense' => 75.50,
                    'net'     => 924.50,
                ],
                'by_category' => [
                    [
                        'category' => 'Food',
                        'total'    => 75.50,
                        'count'    => 1,
                    ],
                ],
                'filters' => [
                    'date_from' => '2026-07-01',
                    'date_to'   => '2026-07-31',
                ],
            ],
        ]);
    }

    public function test_report_export_rate_limiting_triggers_at_6th_request()
    {
        $user = User::factory()->create();

        // Clear export rate limiter cache first
        RateLimiter::clear('reports/export:127.0.0.1');

        // Execute 5 attempts (configured for 5 req/min in api.php)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/reports/export');
            
            // Should be 501 Not Implemented because it's a stub, but passes rate limiter
            $response->assertStatus(501);
        }

        // 6th attempt should trigger 429
        $response = $this->actingAs($user)
            ->getJson('/api/v1/reports/export');
        $response->assertStatus(429);
    }
}
