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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                  // readable label (e.g. "Netflix subscription")
            $table->string('type');                  // income, expense (transfers are not supported)
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 10);
            $table->string('frequency');             // daily, weekly, monthly, yearly
            $table->date('next_run_at');
            $table->string('comment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('next_run_at');
            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'next_run_at']); // Composite index for daily scheduler query

            /*
             * next_run_at — the date of the next run. Every day, the scheduler checks all active
             * records where next_run_at <= today, creates transactions, and advances next_run_at
             * (e.g. +1 week/month). An index on next_run_at is critical for daily scheduler performance.
             */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
