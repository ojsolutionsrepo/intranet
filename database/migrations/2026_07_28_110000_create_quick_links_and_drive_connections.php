<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_links', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url');
            $table->string('category', 32)->default('internal'); // internal|platform|comms
            $table->string('icon', 32)->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('opens_external')->default(true);
            $table->boolean('staff_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('drive_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('account_email')->nullable();
            $table->text('access_token'); // encrypted cast on model
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('scopes')->nullable();
            $table->string('status', 32)->default('active'); // active|revoked|error
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_connections');
        Schema::dropIfExists('quick_links');
    }
};
