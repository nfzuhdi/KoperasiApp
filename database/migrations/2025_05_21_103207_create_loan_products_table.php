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
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Informasi dasar
            $table->string('name'); // Nama Pembiayaan, contoh: Murabahah, Mudharabah
            $table->string('code')->unique();//auto generate
            $table->enum('contract_type', ['Mudharabah', 'Musyarakah', 'Murabahah'])->nullable();

            // Batas pembiayaan
            $table->decimal('min_amount', 15, 2)->nullable(); // minimal pembiayaan
            $table->decimal('max_amount', 15, 2)->nullable(); // maksimal pembiayaan

            // Margin / rate
            $table->decimal('min_rate', 5, 2)->nullable(); // Minimum rate produk (%)
            $table->decimal('max_rate', 5, 2)->nullable(); // Maximum rate produk (%)

            // Tujuan penggunaan (disimpan sebagai string)
            $table->string('usage_purposes', 1000)->nullable(); // contoh: "Buka warung, Bengkel"

            // Tenor dalam bulan, bisa 6, 12, 24
            $table->enum('tenor_months', ['6', '12', '24'])->default('6');
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};