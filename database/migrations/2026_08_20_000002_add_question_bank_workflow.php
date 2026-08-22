<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lms_question_banks', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_question_banks', 'status')) $table->string('status', 30)->default('DRAFT')->index();
            if (! Schema::hasColumn('lms_question_banks', 'submitted_at')) $table->timestamp('submitted_at')->nullable();
            if (! Schema::hasColumn('lms_question_banks', 'approved_at')) $table->timestamp('approved_at')->nullable();
            if (! Schema::hasColumn('lms_question_banks', 'approved_by')) $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
    public function down(): void { Schema::table('lms_question_banks', function (Blueprint $table): void { foreach (['approved_by','approved_at','submitted_at','status'] as $column) if (Schema::hasColumn('lms_question_banks',$column)) $table->dropColumn($column); }); }
};
