<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 5 — Hoàn thiện hệ sinh thái (không SSO):
 * chứng chỉ hoàn thành khóa, khảo sát chất lượng đào tạo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_certificate_templates')) {
            Schema::create('lms_certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->nullable()->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title')->default('Chứng nhận hoàn thành khóa học');
                $table->text('body_html')->nullable();
                $table->string('issuer_name')->nullable();
                $table->decimal('min_score', 8, 2)->nullable(); // thang 10
                $table->decimal('min_progress_pct', 5, 2)->default(80);
                $table->boolean('require_survey')->default(false);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_certificates')) {
            Schema::create('lms_certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('lms_certificate_template_id')->nullable()->constrained('lms_certificate_templates')->nullOnDelete();
                $table->string('code', 40)->unique();
                $table->string('title');
                $table->decimal('final_score', 8, 2)->nullable();
                $table->decimal('progress_pct', 5, 2)->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('issued'); // issued|revoked
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique(['lms_course_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('lms_surveys')) {
            Schema::create('lms_surveys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('is_published')->default(false);
                $table->boolean('is_anonymous')->default(false);
                $table->timestamp('opens_at')->nullable();
                $table->timestamp('closes_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_survey_questions')) {
            Schema::create('lms_survey_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_survey_id')->constrained('lms_surveys')->cascadeOnDelete();
                // rating_1_5 | mcq | text
                $table->string('type', 20)->default('rating_1_5');
                $table->text('stem');
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_survey_responses')) {
            Schema::create('lms_survey_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_survey_id')->constrained('lms_surveys')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('answers'); // {question_id: value}
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique(['lms_survey_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_survey_responses');
        Schema::dropIfExists('lms_survey_questions');
        Schema::dropIfExists('lms_surveys');
        Schema::dropIfExists('lms_certificates');
        Schema::dropIfExists('lms_certificate_templates');
    }
};
