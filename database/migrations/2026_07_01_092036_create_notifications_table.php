<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('data');

            /*
             * data as flexible storage
             * Example:
             * {
             *      "title": "Budget limit reached",
             *      "body": "You have spent 90% of your Food budget",
             *      "budget_id": 3
             * }
             */
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            /*
             * 'read_at' if null means unread, 'timestamp' means read.
             * An index on 'read_at' is needed for a fast query, e.g. “all of a user’s unread notifications” (WHERE `read_at` IS NULL).
            */

            $table->index('user_id');
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
