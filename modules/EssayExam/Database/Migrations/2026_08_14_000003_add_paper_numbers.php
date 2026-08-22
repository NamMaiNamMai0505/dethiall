<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('essay_exam_questions', fn (Blueprint $t) => $t->unsignedInteger('paper_number')->default(1)->after('essay_exam_id'));
        Schema::table('essay_exam_draws', fn (Blueprint $t) => $t->unsignedInteger('paper_number')->default(1)->after('essay_exam_id'));
    }
    public function down(): void {
        Schema::table('essay_exam_questions', fn (Blueprint $t) => $t->dropColumn('paper_number'));
        Schema::table('essay_exam_draws', fn (Blueprint $t) => $t->dropColumn('paper_number'));
    }
};
