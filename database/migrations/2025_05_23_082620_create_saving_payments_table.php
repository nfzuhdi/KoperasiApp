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
        Schema::create('saving_payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Relasi ke rekening simpanan
            $table->foreignId('saving_id')->constrained()->onDelete('cascade');

            // Nominal pembayaran (payment_date removed, using created_at instead)
            $table->unsignedTinyInteger('month'); // 1–12
            $table->unsignedSmallInteger('year'); // 2025, dst
            $table->decimal('amount', 15, 2);
            $table->enum('payment_type', ['deposit', 'withdrawal', 'profit_sharing'])->default('deposit');

            
            // Denda keterlambatan
            $table->decimal('fine', 15, 2)->nullable()->default(0);

            // Metode pembayaran (tunai, transfer, dll)
            $table->string('payment_method')->nullable();

            // Nomor referensi pembayaran (bukti transfer, no transaksi, dll)
            $table->string('reference_number')->nullable();

            // Status pembayaran (untuk approval / verifikasi)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            // Catatan tambahan
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_payments');
    }
};
