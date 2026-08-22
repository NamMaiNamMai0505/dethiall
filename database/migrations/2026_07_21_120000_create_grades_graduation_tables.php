<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tổng kết năm học / xét tốt nghiệp / hồ sơ TN / trích ngang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grade_graduation_sessions')) {
            Schema::create('grade_graduation_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('academic_year', 20)->index(); // 2024-2025
                $table->string('title');
                $table->string('session_type', 32)->default('graduation'); // year_end|graduation
                $table->string('status', 32)->default('draft')->index();
                // draft|open|calculated|pending_approve|approved|locked
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('grade_graduation_results')) {
            Schema::create('grade_graduation_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('grade_graduation_sessions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // học viên
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->unsignedInteger('rank_order')->nullable();
                $table->string('result_status', 40)->default('pending')->index();
                // tot_nghiep|gioi|kha|trung_binh|khong_tn|dinh_chi|thoi_hoc|vang_thi|pending
                $table->decimal('gpa', 5, 2)->nullable();
                $table->decimal('exam_score', 5, 2)->nullable(); // điểm thi TN
                $table->boolean('retake_registered')->default(false);
                $table->text('note')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['session_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('grade_graduation_profiles')) {
            Schema::create('grade_graduation_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->nullable()->constrained('grade_graduation_sessions')->nullOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('major_name')->nullable(); // ngành
                $table->string('cohort')->nullable(); // khóa
                $table->string('system_type')->nullable(); // hệ
                $table->string('duration_text')->nullable(); // thời gian đào tạo
                $table->string('decision_number')->nullable();
                $table->date('decision_date')->nullable();
                $table->date('diploma_sign_date')->nullable();
                $table->string('registry_number')->nullable(); // số vào sổ
                $table->string('series_number')->nullable(); // series văn bằng
                $table->string('series_group')->nullable();
                $table->string('status', 32)->default('draft')->index(); // draft|pending_approve|approved
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('grade_student_profiles')) {
            Schema::create('grade_student_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('student_code', 50)->nullable()->index();
                $table->string('birth_place')->nullable();
                $table->string('ethnicity')->nullable();
                $table->string('religion')->nullable();
                $table->string('id_number', 30)->nullable();
                $table->date('id_issued_at')->nullable();
                $table->string('id_issued_place')->nullable();
                $table->string('permanent_address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('father_name')->nullable();
                $table->string('mother_name')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('grade_conduct_records')) {
            Schema::create('grade_conduct_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('academic_year', 20)->nullable()->index();
                $table->string('conduct_rank', 40)->nullable(); // rèn luyện
                $table->string('discipline', 120)->nullable(); // kỷ luật
                $table->boolean('suspended')->default(false); // tạm ngừng học
                $table->text('note')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'academic_year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_conduct_records');
        Schema::dropIfExists('grade_student_profiles');
        Schema::dropIfExists('grade_graduation_profiles');
        Schema::dropIfExists('grade_graduation_results');
        Schema::dropIfExists('grade_graduation_sessions');
    }
};
