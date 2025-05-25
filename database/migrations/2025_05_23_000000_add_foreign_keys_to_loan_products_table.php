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
        Schema::table('loan_products', function (Blueprint $table) {
            $table->foreignId('journal_account_balance_id')
              ->nullable()
              ->constrained('journal_accounts')
              ->nullOnDelete();

            $table->foreignId('journal_account_income_id')
              ->nullable()
              ->constrained('journal_accounts')
              ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropForeign(['journal_account_balance_id']);
            $table->dropForeign(['journal_account_income_id']);
        });
    }
};