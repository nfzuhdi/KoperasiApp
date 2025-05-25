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
        Schema::create('journal_account_monthly_balances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // Foreign key to journal account
            $table->foreignId('journal_account_id')
                  ->constrained()
                  ->onDelete('cascade');
                  
            // Period information
            $table->integer('year');
            $table->integer('month'); // 1-12
            
            // Balance information
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);

            // Unique constraint to prevent duplicate entries for the same account/month/year
            $table->unique(['journal_account_id', 'year', 'month'] , 'ja_monthly_bal_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_account_monthly_balances');
    }
};