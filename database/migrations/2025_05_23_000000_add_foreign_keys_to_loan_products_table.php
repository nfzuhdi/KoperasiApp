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
            // Akun Jurnal Pencairan
            $table->foreignId('journal_account_balance_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete();
                  
            $table->foreignId('journal_account_balance_credit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete();
            
            // Akun Jurnal Pendapatan
            $table->foreignId('journal_account_income_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete();
                  
            $table->foreignId('journal_account_income_credit_id')
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
            $table->dropForeign(['journal_account_balance_debit_id']);
            $table->dropForeign(['journal_account_balance_credit_id']);
            $table->dropForeign(['journal_account_income_debit_id']);
            $table->dropForeign(['journal_account_income_credit_id']);
            
            
            $table->dropColumn([
                'journal_account_balance_debit_id',
                'journal_account_balance_credit_id',
                'journal_account_income_debit_id',
                'journal_account_income_credit_id'
            ]);
        });
    }
};