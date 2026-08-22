<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=database/migrations/2025_11_02_005135_add_exam_hours_to_subjects_table.php
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            //
            $table->integer('exam_hours')->default(0)->after('practice_hours')->comment('Số tiết thi/kiểm tra');
        });
    }

    /**
     * Reverse the migrations.
     * php artisan migrate:rollback --step=1
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            //
            $table->dropColumn('exam_hours');
        });
    }
};
