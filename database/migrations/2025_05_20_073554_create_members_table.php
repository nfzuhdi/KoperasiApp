<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Wizard;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            //Member's Basic Data
            $table->string('member_id')->unique(); // wajib unik
            $table->string('nik')->unique();           // wajib unik
            $table->string('npwp')->unique()->nullable(); // boleh kosong, tapi jika diisi harus unik
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('religion')->nullable();
            $table->string('member_photo')->nullable();

            //Member's Contact Data (Same page as Basic Data)
            $table->string('telephone_number')->nullable();
            $table->string('email')->nullable();

            //Member's Address Data
            $table->string('RT')->nullable(); 
            $table->string('RW')->nullable();
            $table->char('province_code', 2)->nullable(); 
            $table->char('city_code', 4)->nullable();     
            $table->char('district_code', 7)->nullable();  
            $table->char('village_code', 10)->nullable();  
            $table->text('full_address')->nullable();
            $table->string('postal_code')->nullable();

            //Member's Job Data
            $table->string('occupation')->nullable();
            $table->text('occupation_description')->nullable();
            $table->string('income_source')->nullable();
            $table->string('income_type')->nullable();

            //Member's Spouse Data
            $table->string('spouse_nik')->nullable()->unique();
            $table->string('spouse_full_name')->nullable();
            $table->string('spouse_birth_place')->nullable();
            $table->date('spouse_birth_date')->nullable();
            $table->enum('spouse_gender', ['male', 'female'])->nullable();
            $table->string('spouse_telephone_number')->nullable();
            $table->string('spouse_email')->nullable();

            //Member's Beneficiay Data
            $table->string('heir_relationship')->nullable();
            $table->string('heir_nik')->nullable();
            $table->string('heir_full_name')->nullable();
            $table->string('heir_birth_place')->nullable();
            $table->date('heir_birth_date')->nullable();
            $table->enum('heir_gender', ['male', 'female'])->nullable();
            $table->string('heir_telephone')->nullable();

            //Member's Status Data
            $table->enum('member_status', [
                'pending',
                'active',
                'delinquent',
                'terminated'
            ])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
