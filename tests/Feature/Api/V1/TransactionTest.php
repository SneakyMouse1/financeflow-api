<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_transaction_with_someone_elses_account()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $accountOfUser2 = Account::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->postJson('/api/v1/transactions', [
                'account_id'    => $accountOfUser2->id,
                'type'          => 'expense',
                'amount'        => 100,
                'currency_code' => 'EUR',
                'date'          => '2026-07-03',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['account_id']);
    }

    public function test_cannot_create_transaction_with_someone_elses_category()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user1->id]);
        $categoryOfUser2 = Category::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->postJson('/api/v1/transactions', [
                'account_id'    => $account->id,
                'category_id'   => $categoryOfUser2->id,
                'type'          => 'expense',
                'amount'        => 100,
                'currency_code' => 'EUR',
                'date'          => '2026-07-03',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_creating_transfer_generates_two_related_transactions()
    {
        $user = User::factory()->create();
        $sourceAccount = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000]);
        $targetAccount = Account::factory()->create(['user_id' => $user->id, 'balance' => 500]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/transactions', [
                'account_id'    => $sourceAccount->id,
                'to_account_id' => $targetAccount->id,
                'type'          => 'transfer',
                'amount'        => 200,
                'currency_code' => 'EUR',
                'date'          => '2026-07-03',
            ]);

        $response->assertStatus(201);
        $this->assertEquals(800, $sourceAccount->fresh()->balance);
        $this->assertEquals(700, $targetAccount->fresh()->balance);

        $transactions = Transaction::where('user_id', $user->id)->get();
        $this->assertCount(2, $transactions);
        $this->assertNotNull($transactions[0]->transfer_id);
        $this->assertEquals($transactions[0]->transfer_id, $transactions[1]->transfer_id);
    }

    public function test_cannot_edit_transfer_transactions()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'transfer_id' => 'some-uuid-string',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/transactions/{$transaction->id}", [
                'amount' => 500,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_deleting_transaction_returns_empty_204()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

    public function test_can_list_transactions()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transactions = Transaction::factory()->count(3)->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'amount',
                    'currency_code',
                    'date',
                    'comment',
                    'account',
                    'category',
                    'tags',
                ]
            ]
        ]);
    }

    public function test_index_returns_paginated_transactions()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        Transaction::factory()->count(25)->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/transactions?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
        ]);
        $this->assertEquals(25, $response->json('meta.total'));
    }

    public function test_can_search_transactions_by_tag_and_category_name()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Groceries']);
        
        $tx1 = Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'category_id' => $category->id,
            'comment'     => 'Bought apple juice',
        ]);
        
        $tx2 = Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
            'comment'    => 'Some other thing',
        ]);
        
        $tag = \App\Models\Tag::create(['user_id' => $user->id, 'name' => 'supermarket']);
        $tx2->tags()->attach($tag);
        
        // Search by category name "groceries"
        $response = $this->actingAs($user)->getJson('/api/v1/transactions?search=groceries');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($tx1->id, $response->json('data.0.id'));
        
        // Search by tag name "supermarket"
        $response = $this->actingAs($user)->getJson('/api/v1/transactions?search=supermarket');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($tx2->id, $response->json('data.0.id'));
        
        // Search by comment "apple"
        $response = $this->actingAs($user)->getJson('/api/v1/transactions?search=apple');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($tx1->id, $response->json('data.0.id'));

        // Search by comment "APPLE" (case insensitive test)
        $response = $this->actingAs($user)->getJson('/api/v1/transactions?search=APPLE');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($tx1->id, $response->json('data.0.id'));
    }
}
