<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 9 — T3 SCORM track · T4 proctor flags · H3 versioning · M3 layout · M6 survey templates.
 * (T5/H5 loại bỏ; T4 không webcam/screen share; H4 không offline submit queue)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lms_exams')) {
            Schema::table('lms_exams', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_exams', 'require_fullscreen')) {
                    $table->boolean('require_fullscreen')->default(false)->after('proctor_basic');
                }
                if (! Schema::hasColumn('lms_exams', 'auto_submit_on_leave')) {
                    $table->boolean('auto_submit_on_leave')->default(false)->after('require_fullscreen');
                }
                if (! Schema::hasColumn('lms_exams', 'max_blur_events')) {
                    $table->unsignedSmallInteger('max_blur_events')->nullable()->after('auto_submit_on_leave');
                }
            });
        }

        if (Schema::hasTable('lms_certificate_templates') && ! Schema::hasColumn('lms_certificate_templates', 'layout_json')) {
            Schema::table('lms_certificate_templates', function (Blueprint $table) {
                $table->json('layout_json')->nullable()->after('body_html');
            });
        }

        if (Schema::hasTable('lms_assignment_submissions') && ! Schema::hasColumn('lms_assignment_submissions', 'attempt_no')) {
            Schema::table('lms_assignment_submissions', function (Blueprint $table) {
                $table->unsignedSmallInteger('attempt_no')->default(1)->after('user_id');
                $table->unsignedSmallInteger('version_count')->default(1)->after('attempt_no');
            });
        }

        if (! Schema::hasTable('lms_assignment_submission_versions')) {
            Schema::create('lms_assignment_submission_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lms_assignment_submission_id');
                $table->unsignedSmallInteger('version_no')->default(1);
                $table->text('text_answer')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('disk', 30)->default('public');
                $table->string('status', 20)->default('submitted');
                $table->decimal('score', 8, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('graded_by')->nullable();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();
                $table->unique(['lms_assignment_submission_id', 'version_no'], 'lms_sub_ver_unique');
                $table->foreign('lms_assignment_submission_id', 'lms_sub_ver_sub_fk')
                    ->references('id')->on('lms_assignment_submissions')->cascadeOnDelete();
                $table->foreign('graded_by', 'lms_sub_ver_grader_fk')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('lms_scorm_attempts')) {
            Schema::create('lms_scorm_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lms_scorm_package_id');
                $table->unsignedBigInteger('lms_course_id');
                $table->unsignedBigInteger('user_id');
                $table->string('lesson_status', 40)->nullable();
                $table->string('success_status', 40)->nullable();
                $table->decimal('score_raw', 8, 2)->nullable();
                $table->decimal('score_max', 8, 2)->nullable();
                $table->decimal('score_min', 8, 2)->nullable();
                $table->decimal('score_scaled', 8, 4)->nullable();
                $table->unsignedInteger('session_time_sec')->default(0);
                $table->unsignedInteger('total_time_sec')->default(0);
                $table->text('suspend_data')->nullable();
                $table->string('lesson_location', 500)->nullable();
                $table->json('cmi_data')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('last_commit_at')->nullable();
                $table->timestamps();
                $table->unique(['lms_scorm_package_id', 'user_id'], 'lms_scorm_user_unique');
                $table->foreign('lms_scorm_package_id', 'lms_scorm_att_pkg_fk')
                    ->references('id')->on('lms_scorm_packages')->cascadeOnDelete();
                $table->foreign('lms_course_id', 'lms_scorm_att_course_fk')
                    ->references('id')->on('lms_courses')->cascadeOnDelete();
                $table->foreign('user_id', 'lms_scorm_att_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('lms_survey_templates')) {
            Schema::create('lms_survey_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->foreign('created_by', 'lms_surv_tpl_creator_fk')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('lms_survey_template_questions')) {
            Schema::create('lms_survey_template_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lms_survey_template_id');
                $table->string('type', 20)->default('rating_1_5');
                $table->text('stem');
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('lms_survey_template_id', 'lms_surv_tpl_q_fk')
                    ->references('id')->on('lms_survey_templates')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('lms_surveys') && ! Schema::hasColumn('lms_surveys', 'lms_survey_template_id')) {
            Schema::table('lms_surveys', function (Blueprint $table) {
                $table->unsignedBigInteger('lms_survey_template_id')->nullable()->after('lms_course_id');
                $table->foreign('lms_survey_template_id', 'lms_surv_from_tpl_fk')
                    ->references('id')->on('lms_survey_templates')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_surveys') && Schema::hasColumn('lms_surveys', 'lms_survey_template_id')) {
            Schema::table('lms_surveys', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lms_survey_template_id');
            });
        }
        Schema::dropIfExists('lms_survey_template_questions');
        Schema::dropIfExists('lms_survey_templates');
        Schema::dropIfExists('lms_scorm_attempts');
        Schema::dropIfExists('lms_assignment_submission_versions');

        if (Schema::hasTable('lms_assignment_submissions')) {
            Schema::table('lms_assignment_submissions', function (Blueprint $table) {
                if (Schema::hasColumn('lms_assignment_submissions', 'version_count')) {
                    $table->dropColumn('version_count');
                }
                if (Schema::hasColumn('lms_assignment_submissions', 'attempt_no')) {
                    $table->dropColumn('attempt_no');
                }
            });
        }
        if (Schema::hasTable('lms_certificate_templates') && Schema::hasColumn('lms_certificate_templates', 'layout_json')) {
            Schema::table('lms_certificate_templates', function (Blueprint $table) {
                $table->dropColumn('layout_json');
            });
        }
        if (Schema::hasTable('lms_exams')) {
            Schema::table('lms_exams', function (Blueprint $table) {
                foreach (['max_blur_events', 'auto_submit_on_leave', 'require_fullscreen'] as $col) {
                    if (Schema::hasColumn('lms_exams', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
