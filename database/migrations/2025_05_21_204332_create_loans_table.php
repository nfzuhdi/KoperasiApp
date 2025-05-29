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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Relasi
            $table->foreignId('member_id')->nullable()->constrained()->onDelete('set null'); // Anggota peminjam
            $table->foreignId('loan_product_id')->nullable()->constrained()->onDelete('set null'); // Produk pinjaman
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            // Informasi Akad & Jumlah
            $table->string('account_number')->unique();
            $table->decimal('loan_amount', 15, 2)->nullable();
            $table->decimal('margin_amount', 15, 2)->nullable(); // Persentase margin untuk Murabahah atau bagi hasil koperasi

            //Case Murabahah
            $table->decimal('purchase_price', 15, 2)->nullable(); // harga beli
            $table->decimal('selling_price', 15, 2)->nullable(); // harga jual (purchase_price + margin)
        
            // Informasi Jaminan
            $table->enum('collateral_type', [ 'bpkb', 'shm'])->nullable();
            
            // Jaminan BPKB
            $table->decimal('bpkb_collateral_value', 15, 2)->nullable(); // Harga jaminan
            $table->string('bpkb_owner_name')->nullable(); // Nama pemilik
            $table->string('bpkb_number')->nullable(); // Nomor BPKB
            $table->string('bpkb_vehicle_number')->nullable(); // Nomor polisi
            $table->string('bpkb_vehicle_brand')->nullable(); // Merk kendaraan
            $table->string('bpkb_vehicle_type')->nullable(); // Tipe kendaraan
            $table->integer('bpkb_vehicle_year')->nullable(); // Tahun kendaraan
            $table->string('bpkb_frame_number')->nullable(); // Nomor rangka
            $table->string('bpkb_engine_number')->nullable(); // Nomor mesin
            
            // Jaminan SHM
            $table->decimal('shm_collateral_value', 15, 2)->nullable(); // Harga jaminan
            $table->string('shm_owner_name')->nullable(); // Nama pemilik
            $table->string('shm_certificate_number')->nullable(); // Nomor sertifikat
            $table->decimal('shm_land_area', 10, 2)->nullable(); // Luas tanah m²
            $table->text('shm_land_location')->nullable(); // Lokasi tanah

            // Status
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->enum('disbursement_status', ['not_disbursed', 'disbursed'])->default('not_disbursed'); //sudah dicairkan
            $table->enum('payment_status', ['not_paid','on_going', 'paid'])->default('not_paid');
            $table->date('disbursed_at')->nullable(); //tanggal dicairkan
            $table->date('paid_off_at')->nullable();
            $table->timestamp('completed_at')->nullable(); // Tanggal penyelesaian pinjaman

            // Approval
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approved_at')->nullable();
            $table->string('rejected_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};