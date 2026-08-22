<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('essay_exam_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('essay_exam_questions', 'question_type')) {
                $table->string('question_type', 20)->default('essay')->after('question_number');
            }
            if (! Schema::hasColumn('essay_exam_questions', 'options')) {
                $table->json('options')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('essay_exam_questions', function (Blueprint $table): void {
            if (Schema::hasColumn('essay_exam_questions', 'options')) {
                $table->dropColumn('options');
            }
            if (Schema::hasColumn('essay_exam_questions', 'question_type')) {
                $table->dropColumn('question_type');
            }
        });
    }
};
