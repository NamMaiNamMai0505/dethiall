<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('essay_exams', fn (Blueprint $t) => $t->foreignId('class_id')->nullable()->after('subject_id')->constrained('classes')->nullOnDelete()); }
    public function down(): void { Schema::table('essay_exams', fn (Blueprint $t) => $t->dropForeign(['class_id'])->dropColumn('class_id')); }
};
