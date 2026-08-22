<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=database/migrations/2025_11_02_125102_update_subjects_table_code_unique_with_soft_delete.php
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Drop the old unique constraint on code column
            $table->dropUnique('subjects_code_unique');

            // Add composite unique constraint: code + deleted_at
            // This allows same code if one record is soft deleted
            $table->unique(['code', 'deleted_at'], 'subjects_code_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     * php artisan migrate:rollback --step=1
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('subjects_code_deleted_at_unique');

            // Restore the original unique constraint on code only
            $table->unique('code', 'subjects_code_unique');
        });
    }
};
