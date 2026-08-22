<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_details', function (Blueprint $table) { // Adjust table name nếu khác
            // Composite index cho query hour check (subject + ts + lesson_type)
            $table->index(['subject_id', 'training_schedule_id', 'lesson_type'], 'idx_schedule_subject_ts_type');
            
        });
    }

    public function down(): void
    {
        Schema::table('schedule_details', function (Blueprint $table) {
            $table->dropIndex('idx_schedule_subject_ts_type');
        });
    }
};