<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('essay_exams', function (Blueprint $table) { $table->string('academic_year',20)->nullable()->index(); $table->string('semester',30)->nullable()->index(); $table->string('difficulty',30)->nullable()->index(); $table->string('exam_type',50)->nullable()->index(); }); }
    public function down(): void { Schema::table('essay_exams', function (Blueprint $table) { $table->dropColumn(['academic_year','semester','difficulty','exam_type']); }); }
};
