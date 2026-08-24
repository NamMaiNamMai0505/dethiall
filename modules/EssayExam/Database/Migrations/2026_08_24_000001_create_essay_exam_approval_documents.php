<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('essay_exam_approval_documents')) return;

        Schema::create('essay_exam_approval_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('essay_exam_id')->constrained('essay_exams')->cascadeOnDelete();
            $table->string('decision_code', 80)->unique();
            $table->string('title');
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('class_name')->nullable();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('subject_name')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signature_method', 20)->nullable();
            $table->string('signature_path')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status', 30)->default('SENT_TO_EXAM_OFFICE')->index();
            $table->timestamp('sent_to_exam_office_at')->nullable();
            $table->foreignId('sent_to_exam_office_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['essay_exam_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essay_exam_approval_documents');
    }
};
