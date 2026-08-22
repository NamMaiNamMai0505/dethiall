<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('essay_exam_questions', function (Blueprint $table) {
            $table->string('paper_status', 30)->default('DRAFT')->after('paper_number')->index();
        });
    }
    public function down(): void { Schema::table('essay_exam_questions', fn (Blueprint $table) => $table->dropColumn('paper_status')); }
};
