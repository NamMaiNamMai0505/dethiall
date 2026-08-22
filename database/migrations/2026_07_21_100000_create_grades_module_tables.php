<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 5–6: Quản lý điểm (GV / Manager / Super-admin).
 * workflow: draft → locked_by_gv → pending_pdot → approved | revision_requested
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grade_books')) {
            Schema::create('grade_books', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('instructor_id')->nullable()->constrained('instructors')->nullOnDelete();
                $table->foreignId('lms_course_id')->nullable()->constrained('lms_courses')->nullOnDelete();
                $table->string('academic_year', 20)->nullable()->index();
                $table->string('title');
                $table->string('status', 32)->default('draft')->index();
                // draft|open|locked_gv|pending_pdot|approved|revision
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['class_id', 'subject_id', 'academic_year', 'title'], 'grade_books_unique_ctx');
            });
        }

        if (! Schema::hasTable('grade_columns')) {
            Schema::create('grade_columns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_book_id')->constrained('grade_books')->cascadeOnDelete();
                $table->string('code', 32); // oral_15, period_1, midterm, final, custom
                $table->string('name');
                $table->string('source', 32)->default('manual'); // manual|lms
                $table->decimal('max_score', 8, 2)->default(10);
                $table->decimal('weight', 8, 2)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_locked')->default(false);
                $table->boolean('pdot_only')->default(false); // điểm thi do PDOT nhập
                $table->timestamps();
                $table->unique(['grade_book_id', 'code']);
            });
        }

        if (! Schema::hasTable('grade_cells')) {
            Schema::create('grade_cells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_book_id')->constrained('grade_books')->cascadeOnDelete();
                $table->foreignId('grade_column_id')->constrained('grade_columns')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // student
                $table->decimal('score', 8, 2)->nullable();
                $table->string('note', 500)->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['grade_column_id', 'user_id']);
                $table->index(['grade_book_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('grade_change_requests')) {
            Schema::create('grade_change_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_book_id')->constrained('grade_books')->cascadeOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->string('status', 32)->default('pending')->index();
                // pending|pdot_ok|approved|rejected
                $table->text('reason');
                $table->text('pdot_note')->nullable();
                $table->text('director_note')->nullable();
                $table->foreignId('pdot_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('pdot_reviewed_at')->nullable();
                $table->foreignId('director_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('director_reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('grade_audit_logs')) {
            Schema::create('grade_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_book_id')->nullable()->constrained('grade_books')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 64);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['grade_book_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('export_templates')) {
            Schema::create('export_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('scope', 32)->index(); // dashboard|lms|grades|shared
                $table->string('feature_key', 64)->index(); // grades.score_sheet, lhl.plan, ...
                $table->string('file_path');
                $table->string('disk', 32)->default('local');
                $table->string('mime', 128)->nullable();
                $table->string('original_name')->nullable();
                $table->json('placeholders')->nullable(); // detected {{vars}}
                $table->json('cell_map')->nullable(); // sheet/cell => placeholder
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('export_templates');
        Schema::dropIfExists('grade_audit_logs');
        Schema::dropIfExists('grade_change_requests');
        Schema::dropIfExists('grade_cells');
        Schema::dropIfExists('grade_columns');
        Schema::dropIfExists('grade_books');
    }
};
