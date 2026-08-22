<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('essay_exam_draws', function(Blueprint $table){ if(!Schema::hasColumn('essay_exam_draws','question_points')) $table->decimal('question_points',6,2)->nullable()->after('question_ids'); }); }
    public function down(): void { Schema::table('essay_exam_draws', function(Blueprint $table){ if(Schema::hasColumn('essay_exam_draws','question_points')) $table->dropColumn('question_points'); }); }
};
