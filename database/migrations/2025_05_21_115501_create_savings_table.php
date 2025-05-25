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
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            //Foreign Keys Relations
            $table->foreignId('member_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('saving_product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            //Account Information
            $table->string('account_number')->unique(); // nomor rekening simpanan
            $table->decimal('balance', 15, 2)->default(0); // saldo saat ini
            $table->enum('status', ['pending','active', 'closed','blocked','declined'])->default('pending');

            //Approval Information
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('rejected_reason')->nullable();

            $table->integer('payment_period')->nullable();
            $table->date('maturity_date')->nullable();
            $table->date('next_due_date')->nullable(); // Tanggal jatuh tempo pembayaran berikutnya
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};



