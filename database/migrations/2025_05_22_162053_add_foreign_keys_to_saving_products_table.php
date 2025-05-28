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
        Schema::table('saving_products', function (Blueprint $table) {
            
            // Untuk transaksi setoran
            $table->foreignId('journal_account_deposit_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun debit saat simpanan disetor (biasanya Kas)');
                  
            $table->foreignId('journal_account_deposit_credit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun kredit saat simpanan disetor (biasanya akun simpanan)');
            
            // Untuk transaksi penarikan
            $table->foreignId('journal_account_withdrawal_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun debit saat penarikan (biasanya akun simpanan)');
                  
            $table->foreignId('journal_account_withdrawal_credit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun kredit saat penarikan (biasanya Kas)');
            
            // Untuk transaksi bagi hasil
            $table->foreignId('journal_account_profitsharing_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun debit saat pembagian hasil (biasanya akun bagi hasil)');
                  
            $table->foreignId('journal_account_profitsharing_credit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun kredit saat pembagian hasil (biasanya Kas atau Pendapatan)');
            
            // Untuk Denda saja (renamed from profit to penalty)
            $table->foreignId('journal_account_penalty_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun debit untuk pencatatan denda');
                  
            $table->foreignId('journal_account_penalty_credit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete()
                  ->comment('Akun kredit untuk pencatatan denda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_products', function (Blueprint $table) {
            // Hapus foreign key baru
            $table->dropForeign(['journal_account_deposit_debit_id']);
            $table->dropForeign(['journal_account_deposit_credit_id']);
            $table->dropForeign(['journal_account_withdrawal_debit_id']);
            $table->dropForeign(['journal_account_withdrawal_credit_id']);
            $table->dropForeign(['journal_account_profitsharing_debit_id']);
            $table->dropForeign(['journal_account_profitsharing_credit_id']);
            $table->dropForeign(['journal_account_penalty_debit_id']);
            $table->dropForeign(['journal_account_penalty_credit_id']);
            
            // Hapus kolom
            $table->dropColumn([
                'journal_account_deposit_debit_id',
                'journal_account_deposit_credit_id',
                'journal_account_withdrawal_debit_id',
                'journal_account_withdrawal_credit_id',
                'journal_account_profitsharing_debit_id',
                'journal_account_profitsharing_credit_id',
                'journal_account_penalty_debit_id',
                'journal_account_penalty_credit_id'
            ]);
        });
    }
};



