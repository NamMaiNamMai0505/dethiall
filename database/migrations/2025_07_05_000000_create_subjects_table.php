<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên môn học');
            $table->string('code', 50)->unique()->comment('Mã môn học');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->foreignId('specialization_id')->constrained('specializations')->comment('Thuộc lớp');
            $table->integer('credits')->default(1)->comment('Số tín chỉ');
            $table->integer('theory_hours')->default(0)->comment('Số tiết lý thuyết');
            $table->integer('practice_hours')->default(0)->comment('Số tiết thực hành');
            $table->integer('self_study_hours')->default(0)->comment('Số tiết tự học');
            $table->enum('level', ['basic', 'intermediate', 'advanced'])
                  ->default('basic')
                  ->comment('Cấp độ môn học');
            $table->json('prerequisites')->nullable()->comment('Môn học tiên quyết');
            $table->enum('assessment_method', ['exam', 'assignment', 'project', 'combined', 'multiple_choice_questions'])
                  ->default('exam')
                  ->comment('Phương pháp đánh giá');
            $table->boolean('is_required')->default(true)->comment('Bắt buộc hay tự chọn');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');

            $table->unsignedBigInteger('created_by')->nullable()->comment('Người tạo');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Người cập nhật');

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['specialization_id', 'is_active']);
            $table->index(['level', 'is_required']);
            $table->index('assessment_method');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
