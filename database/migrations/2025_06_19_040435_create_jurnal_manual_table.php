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
        Schema::create('jurnal_manual', function (Blueprint $table) {
        $table->id();
        $table->string('nama_transaksi');
        $table->date('tanggal');
        $table->decimal('nominal', 15, 2);
        $table->text('catatan')->nullable();
        $table->timestamps();
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        $table->foreignId('journal_account_transaction_debit_id')
                  ->nullable()
                  ->constrained('journal_accounts')
                  ->nullOnDelete();
                  
            $table->foreignId('journal_account_transaction_credit_id')
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
        Schema::dropIfExists('jurnal_manual');
    }
};
