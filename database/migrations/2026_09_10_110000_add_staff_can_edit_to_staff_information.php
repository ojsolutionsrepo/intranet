<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_information', function (Blueprint $table) {
            $table->boolean('staff_can_edit')->default(true)->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('staff_information', function (Blueprint $table) {
            $table->dropColumn('staff_can_edit');
        });
    }
};
