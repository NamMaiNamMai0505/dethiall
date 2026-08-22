<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('schedule_details', function (Blueprint $table) {
            $table->enum('lesson_type', ['theory', 'practice', 'self_study','final_exam'])
                  ->default('theory')
                  ->after('classroom_id')
                  ->comment('Loại buổi học: lý thuyết / thực hành / tự học');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('schedule_details', function (Blueprint $table) {
            $table->dropColumn('lesson_type');
        });
    }
};
