<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { if (!Schema::hasTable('essay_exam_draws')) Schema::create('essay_exam_draws', function(Blueprint $table){$table->id();$table->foreignId('essay_exam_id')->constrained('essay_exams')->cascadeOnDelete();$table->string('draw_code')->unique();$table->string('draw_type',8);$table->string('class_name')->nullable();$table->date('exam_date')->nullable();$table->time('exam_time')->nullable();$table->string('location')->nullable();$table->foreignId('drawn_by_user_id')->nullable()->constrained('users')->nullOnDelete();$table->timestamp('drawn_at');$table->timestamps();$table->index(['essay_exam_id','draw_type']);}); }
    public function down(): void { Schema::dropIfExists('essay_exam_draws'); }
};
