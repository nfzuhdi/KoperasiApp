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
        Schema::create('saving_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            //Product Header
            $table->string('code')->unique();
            $table->string('savings_product_name');
            $table->text('description')->nullable();

            //Savings Type
            $table->enum('savings_type', ['principal', 'mandatory', 'deposit','time_deposit']);

            //General Settings
            $table->decimal('min_deposit', 15, 2)->default(0);
            $table->decimal('max_deposit', 15, 2)->nullable();
            $table->decimal('minimal_balance',15, 2)->default(0);
            $table->boolean('is_withdrawable')->default(true);
            $table->boolean('is_mandatory_routine')->default(false);
            $table->enum('deposit_period', ['weekly','monthly','yearly'])->nullable();
            $table->enum('contract_type', ['Mudharabah', 'Wadiah'])->nullable();

            //Time Deposit Fields
            $table->integer('tenor_months')->nullable(); 
            $table->decimal('early_withdrawal_penalty', 15, 2)->nullable();

            //Bagi Hasil Fields (Just For Documenting)
            $table->enum('profit_sharing_type', ['amount', 'ratio'])->nullable();
            $table->decimal('profit_sharing_amount', 15, 2)->nullable();
            $table->decimal('member_ratio',5,2)->nullable();
            $table->decimal('koperasi_ratio')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_products');
    }
};



