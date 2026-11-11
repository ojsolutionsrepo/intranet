<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_information', function (Blueprint $table) {
            // Google Form uploads live in Drive; the import keeps their links.
            $table->string('photo_drive_url', 500)->nullable()->after('photo_path');
            $table->string('cv_drive_url', 500)->nullable()->after('cv_original_name');
            $table->string('dbs_check', 500)->nullable()->after('cv_drive_url');
        });
    }

    public function down(): void
    {
        Schema::table('staff_information', function (Blueprint $table) {
            $table->dropColumn(['photo_drive_url', 'cv_drive_url', 'dbs_check']);
        });
    }
};
