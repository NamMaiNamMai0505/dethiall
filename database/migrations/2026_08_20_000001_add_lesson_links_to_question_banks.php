<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lms_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_questions', 'lms_lesson_id')) {
                $table->foreignId('lms_lesson_id')->nullable()->after('lms_question_bank_id')->constrained('lms_lessons')->nullOnDelete();
                $table->index(['lms_question_bank_id', 'lms_lesson_id']);
            }
        });
        Schema::table('essay_exam_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('essay_exam_questions', 'lms_lesson_id')) {
                $table->foreignId('lms_lesson_id')->nullable()->after('essay_exam_id')->constrained('lms_lessons')->nullOnDelete();
                $table->index(['essay_exam_id', 'lms_lesson_id']);
            }
        });
    }
    public function down(): void {
        Schema::table('essay_exam_questions', function (Blueprint $table): void { if (Schema::hasColumn('essay_exam_questions', 'lms_lesson_id')) $table->dropConstrainedForeignId('lms_lesson_id'); });
        Schema::table('lms_questions', function (Blueprint $table): void { if (Schema::hasColumn('lms_questions', 'lms_lesson_id')) $table->dropConstrainedForeignId('lms_lesson_id'); });
    }
};
