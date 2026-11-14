<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 32)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->json('schema');
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['request_type_id', 'version']);
        });

        Schema::create('request_number_sequences', function (Blueprint $table) {
            $table->string('month_key', 6)->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique();
            $table->foreignId('request_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('form_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('form_data')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('current_stage_order')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('department_id');
            $table->index('requester_id');
            $table->index('submitted_at');
        });

        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('field_key', 64)->nullable();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('file_size');
            $table->string('storage_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('request_number_sequences');
        Schema::dropIfExists('form_templates');
        Schema::dropIfExists('request_types');
    }
};
