<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_only_own_tags()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Tag::factory()->create(['user_id' => $user1->id, 'name' => 'Shopping']);
        Tag::factory()->create(['user_id' => $user2->id, 'name' => 'Travel']);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/tags');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Shopping');
    }

    public function test_can_create_tag()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tags', [
                'name'  => 'Salary',
                'color' => '#10b981',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tags', [
            'user_id' => $user->id,
            'name'    => 'Salary',
        ]);
    }

    public function test_can_update_tag()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'Old Tag']);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/tags/{$tag->id}", [
                'name' => 'New Tag',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('New Tag', $tag->fresh()->name);
    }

    public function test_deleting_tag_returns_empty_204()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_cannot_view_or_modify_someone_elses_tag()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1)
            ->getJson("/api/v1/tags/{$tag->id}")
            ->assertStatus(403);

        $this->actingAs($user1)
            ->patchJson("/api/v1/tags/{$tag->id}", ['name' => 'Hack'])
            ->assertStatus(403);

        $this->actingAs($user1)
            ->deleteJson("/api/v1/tags/{$tag->id}")
            ->assertStatus(403);
    }
}
