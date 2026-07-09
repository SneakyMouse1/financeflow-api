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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('currency_code', 10)->default('EUR');
            $table->decimal('balance', 15, 2)->default(0); // for the money always decimal!!!!
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
