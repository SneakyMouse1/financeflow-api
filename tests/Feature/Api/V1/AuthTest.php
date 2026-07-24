<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_and_response_uses_user_resource()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonMissingPath('data.user.password');
        $response->assertJsonMissingPath('data.user.remember_token');
        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'settings',
                    'created_at',
                ],
                'token',
            ],
        ]);
    }

    public function test_login_rate_limiting_triggers_at_11_requests()
    {
        // Clear rate limiter cache first
        RateLimiter::clear('login:127.0.0.1');

        // Execute 10 attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ]);
            $response->assertStatus(422);
        }

        // 11th attempt should trigger rate limiting (429)
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);
        $response->assertStatus(429);
    }

    public function test_can_request_password_reset_always_returns_200()
    {
        // Notification::fake() prevents actual emails from being sent
        Notification::fake();

        // Existing email — should return 200 (not 404)
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'existing@example.com'])
            ->assertStatus(200)
            ->assertJsonPath('data.message', 'If an account with that email exists, a password reset link has been sent.');

        // Non-existing email — should ALSO return 200 (avoid user enumeration)
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'nobody@example.com'])
            ->assertStatus(200);
    }

    public function test_password_reset_rate_limiting_triggers_at_6th_request()
    {
        Notification::fake();

        // Perform 5 attempts (allowed)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/password/forgot', ['email' => 'test@example.com'])
                ->assertStatus(200);
        }

        // 6th attempt should trigger 429 Too Many Requests
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'test@example.com'])
            ->assertStatus(429);
    }

    public function test_can_delete_account_with_correct_password()
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/account', [
                'current_password' => 'secret123',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.message', 'Account deleted successfully.');

        // User should be deleted (no SoftDeletes on User model) and tokens revoked
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_cannot_delete_account_with_wrong_password()
    {
        $user = User::factory()->create(['password' => bcrypt('correct_password')]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/account', [
                'current_password' => 'wrong_password',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);

        // User should NOT be deleted
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
