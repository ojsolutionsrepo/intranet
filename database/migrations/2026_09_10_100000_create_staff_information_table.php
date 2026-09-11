<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('full_name');
            $table->string('preferred_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('residential_address')->nullable();

            $table->string('nok_full_name')->nullable();
            $table->string('nok_phone')->nullable();
            $table->text('nok_address')->nullable();
            $table->string('nok_relationship')->nullable();

            $table->string('job_title')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('start_date')->nullable();
            $table->text('role_responsibilities')->nullable();
            $table->string('work_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('linkedin_url')->nullable();

            $table->string('photo_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_original_name')->nullable();

            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->text('account_number')->nullable(); // encrypted cast
            $table->text('sort_code')->nullable(); // encrypted cast

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_information');
    }
};
