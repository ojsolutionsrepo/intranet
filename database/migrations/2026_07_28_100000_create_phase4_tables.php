<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_health', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // sso|drive|plane|governex
            $table->string('driver')->nullable();
            $table->string('status', 32)->default('unknown'); // ok|degraded|down|unknown
            $table->string('circuit', 16)->default('closed'); // closed|open|half_open
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('sso_jtis', function (Blueprint $table) {
            $table->string('jti', 64)->primary();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('external_ref')->nullable();
            $table->string('source', 32)->default('manual'); // plane|governex|manual
            $table->string('status', 32)->default('active');
            $table->text('summary')->nullable();
            $table->string('rag')->nullable(); // red/amber/green
            $table->string('deep_link')->nullable();
            $table->json('audience')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['source', 'external_ref']);
            $table->index(['status', 'synced_at']);
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_on')->nullable();
            $table->string('status', 32)->default('planned');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('sso_jtis');
        Schema::dropIfExists('integration_health');
    }
};
