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
        Schema::create('journal_accounts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            //Account Information
            $table->string('account_number')->unique();
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('account_position', ['debit', 'credit']);
            
            //Account Hierarchy
            $table->boolean('is_sub_account')->default(false);
            $table->foreignId('parent_account_id')->nullable()->constrained('journal_accounts')->onDelete('set null');

            //Opening Balance
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('balance',15,2)->default(0);
            $table->date('opening_balance_date')->nullable();

            //Status
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_accounts');
    }
};
