<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_delete_account_and_receives_empty_204()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_deleting_account_soft_deletes_its_transactions()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/accounts/{$account->id}");

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_cannot_delete_or_view_someone_elses_account()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user2->id]);

        // Try viewing
        $this->actingAs($user1)
            ->getJson("/api/v1/accounts/{$account->id}")
            ->assertStatus(403);

        // Try deleting
        $this->actingAs($user1)
            ->deleteJson("/api/v1/accounts/{$account->id}")
            ->assertStatus(403);
    }
}
