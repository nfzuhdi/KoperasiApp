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
        Schema::create('laporan_posisi_keuangan', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // Period information
            $table->integer('bulan'); // 1-12
            $table->integer('tahun');
            
            // Account information
            $table->foreignId('akun_id')->constrained('journal_accounts')->onDelete('cascade');
            $table->string('kode_akun');
            $table->string('nama_akun');
            $table->enum('jenis_akun', ['asset', 'liability', 'equity']);
            
            // Classification for balance sheet
            $table->enum('klasifikasi', [
                'aktiva_lancar',
                'aktiva_tetap', 
                'kewajiban',
                'ekuitas'
            ]);
            
            // Balance amount
            $table->decimal('saldo', 15, 2)->default(0);
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['akun_id', 'bulan', 'tahun'], 'laporan_posisi_keuangan_unique');
            
            // Indexes for better performance
            $table->index(['bulan', 'tahun']);
            $table->index(['klasifikasi']);
            $table->index(['kode_akun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_posisi_keuangan');
    }
};
