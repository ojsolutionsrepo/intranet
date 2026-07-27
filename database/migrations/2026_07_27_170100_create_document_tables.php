<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('visibility', 32)->default('all'); // all|department|team|users
            $table->json('audience')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('document_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('visibility', 32)->default('inherit'); // inherit|all|department|team|users
            $table->json('audience')->nullable();
            $table->boolean('is_policy')->default(false);
            $table->boolean('mandatory_ack')->default(false);
            $table->date('review_at')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('trashed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['category_id', 'slug']);
            $table->index(['is_policy', 'trashed_at']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('storage_ref');
            $table->string('disk')->default('local');
            $table->string('original_filename');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum_sha256', 64)->index();
            $table->longText('extracted_text')->nullable();
            $table->string('changelog')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('document_versions')
                ->nullOnDelete();
        });

        Schema::create('document_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['document_version_id', 'user_id'], 'doc_ack_version_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_acknowledgements');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_categories');
    }
};
