<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 — Nội dung học tập: materials, SCORM, forum, chat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_materials')) {
            Schema::create('lms_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('lms_lesson_id')->nullable()->constrained('lms_lessons')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                // pdf|slide|video|document|image|archive|other|scorm
                $table->string('kind', 30)->default('document')->index();
                $table->string('disk', 30)->default('public');
                $table->string('path'); // relative path on disk
                $table->string('original_name')->nullable();
                $table->string('mime', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->boolean('is_published')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_scorm_packages')) {
            Schema::create('lms_scorm_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('lms_material_id')->nullable()->constrained('lms_materials')->nullOnDelete();
                $table->string('title');
                $table->string('version', 20)->nullable(); // 1.2 | 2004
                $table->string('launch_path')->nullable(); // relative to extract dir
                $table->string('extract_path'); // directory on public disk
                $table->string('manifest_path')->nullable();
                $table->json('meta')->nullable();
                $table->boolean('is_published')->default(true);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_forum_topics')) {
            Schema::create('lms_forum_topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('body');
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->unsignedInteger('replies_count')->default(0);
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lms_course_id', 'last_reply_at']);
            });
        }

        if (! Schema::hasTable('lms_forum_replies')) {
            Schema::create('lms_forum_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_forum_topic_id')->constrained('lms_forum_topics')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_chat_messages')) {
            Schema::create('lms_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamps();
                $table->index(['lms_course_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_chat_messages');
        Schema::dropIfExists('lms_forum_replies');
        Schema::dropIfExists('lms_forum_topics');
        Schema::dropIfExists('lms_scorm_packages');
        Schema::dropIfExists('lms_materials');
    }
};
