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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('loan_id')->constrained()->onDelete('cascade');

            $table->date('due_date')->nullable(); 
            $table->unsignedTinyInteger('month'); 
            $table->unsignedSmallInteger('year'); 
            $table->decimal('amount', 15, 2);

            $table->unsignedInteger('payment_period')->nullable();

            $table->decimal('fine', 15, 2)->nullable(); 
            $table->boolean('is_late')->default(false);

            $table->string('payment_method')->nullable();

            $table->string('reference_number')->nullable();

            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->unique(['loan_id', 'payment_period']);

            $table->index(['loan_id', 'payment_period']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};