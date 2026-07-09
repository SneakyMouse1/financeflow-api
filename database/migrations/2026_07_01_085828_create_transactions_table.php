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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            /*
             * nullable + nullOnDelete() — the category may not be specified (when transferring between accounts),
             * and if the category is deleted, the transaction will not be deleted; the `category_id` will simply become null
             */
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // income, expense, transfer
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 10);
            $table->date('date');
            $table->string('comment')->nullable();
            /*
             * transfer_id — UUID — the same for both parts of the transfer (expense on account A and income on account B).
             * The UUID is generated in TransactionService when the transfer is created
             */
            $table->uuid('transfer_id')->nullable();
            $table->foreignId('related_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('account_id');
            $table->index('category_id');
            $table->index('date');
            $table->index('created_at');
            $table->index('currency_code');
            $table->index('transfer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
