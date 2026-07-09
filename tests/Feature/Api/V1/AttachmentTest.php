<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_attachment_to_own_transaction()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/attachments', [
                'transaction_id' => $transaction->id,
                'file'           => $file,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'file_name',
                'mime',
                'size',
                'url',
                'created_at',
            ],
        ]);

        $attachment = Attachment::first();
        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_cannot_upload_attachment_to_someone_elses_transaction()
    {
        Storage::fake('public');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $accountOfUser2 = Account::factory()->create(['user_id' => $user2->id]);
        $transactionOfUser2 = Transaction::factory()->create([
            'user_id'    => $user2->id,
            'account_id' => $accountOfUser2->id,
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($user1)
            ->postJson('/api/v1/attachments', [
                'transaction_id' => $transactionOfUser2->id,
                'file'           => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['transaction_id']);
    }

    public function test_can_delete_attachment_and_removes_file_from_disk()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        // Step 1: Upload
        $uploadResponse = $this->actingAs($user)
            ->postJson('/api/v1/attachments', [
                'transaction_id' => $transaction->id,
                'file'           => $file,
            ]);

        $attachmentId = $uploadResponse->json('data.id');
        $attachment = Attachment::find($attachmentId);

        Storage::disk('public')->assertExists($attachment->path);

        // Step 2: Delete
        $deleteResponse = $this->actingAs($user)
            ->deleteJson("/api/v1/attachments/{$attachment->id}");

        $deleteResponse->assertStatus(204);
        $this->assertEmpty($deleteResponse->getContent());
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->path);
    }

    public function test_cannot_delete_someone_elses_attachment()
    {
        Storage::fake('public');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account2 = Account::factory()->create(['user_id' => $user2->id]);
        $transaction2 = Transaction::factory()->create([
            'user_id'    => $user2->id,
            'account_id' => $account2->id,
        ]);

        $attachment = Attachment::create([
            'transaction_id' => $transaction2->id,
            'file_name'      => 'receipt.jpg',
            'mime'           => 'image/jpeg',
            'size'           => 1024,
            'path'           => 'attachments/fake.jpg',
        ]);

        $response = $this->actingAs($user1)
            ->deleteJson("/api/v1/attachments/{$attachment->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_attachment_if_transaction_is_soft_deleted()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id'    => $user->id,
            'account_id' => $account->id,
        ]);

        $attachment = Attachment::create([
            'transaction_id' => $transaction->id,
            'file_name'      => 'receipt.jpg',
            'mime'           => 'image/jpeg',
            'size'           => 1024,
            'path'           => 'attachments/fake.jpg',
        ]);

        // Soft-delete the transaction
        $transaction->delete();

        // Attempting to delete the attachment should yield 403 (not crash 500)
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/attachments/{$attachment->id}");

        $response->assertStatus(403);
    }
}
