<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('essay_exam_draws', function(Blueprint $table){ if(!Schema::hasColumn('essay_exam_draws','question_ids')) $table->json('question_ids')->nullable()->after('paper_number'); }); }
    public function down(): void { Schema::table('essay_exam_draws', function(Blueprint $table){ if(Schema::hasColumn('essay_exam_draws','question_ids')) $table->dropColumn('question_ids'); }); }
};
