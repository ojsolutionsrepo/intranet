<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->string('drive_file_id')->nullable()->after('disk');
            $table->string('drive_revision_id')->nullable()->after('drive_file_id');
            $table->index('drive_file_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropIndex(['drive_file_id']);
            $table->dropColumn(['drive_file_id', 'drive_revision_id']);
        });
    }
};
