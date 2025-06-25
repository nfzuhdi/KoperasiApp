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
        Schema::create('laporan_arus_kas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // Period information
            $table->integer('bulan'); // 1-12
            $table->integer('tahun');
            
            // Transaction information
            $table->date('tanggal');
            $table->string('keterangan');
            
            // Account information (optional, for reference)
            $table->foreignId('akun_id')->nullable()->constrained('journal_accounts')->onDelete('set null');
            $table->string('nama_akun')->nullable();
            
            // Cash flow classification
            $table->enum('kategori', ['operasi', 'investasi', 'pendanaan']);
            $table->enum('jenis', ['masuk', 'keluar']);
            
            // Amount
            $table->decimal('jumlah', 15, 2)->default(0);
            
            // Indexes for better performance
            $table->index(['bulan', 'tahun']);
            $table->index(['kategori', 'jenis']);
            $table->index(['tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_arus_kas');
    }
};
