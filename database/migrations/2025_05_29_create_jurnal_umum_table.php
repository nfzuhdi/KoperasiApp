<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_umum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_bayar');
            $table->string('no_ref')->nullable();
            $table->string('no_transaksi');
            $table->foreignId('akun_id')->constrained('journal_accounts');
            $table->string('keterangan');
            $table->decimal('debet', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            
            // Foreign key untuk transaksi simpanan
            $table->foreignId('saving_payment_id')->nullable()
                ->constrained('saving_payments')
                ->onDelete('restrict');

            // Foreign key untuk transaksi pinjaman    
            $table->foreignId('loan_payment_id')->nullable()
                ->constrained('loan_payments')
                ->onDelete('restrict');
                
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_umum');
    }
};