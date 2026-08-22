<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrective/idempotent migration for installations that loaded the first
     * calendar-year migration while the legacy column was still named academic_year.
     */
    public function up(): void
    {
        $this->convertColumn('standard_hour_norms');
        $this->convertColumn('research_hour_norms');
        $this->convertColumn('instructor_conversion_records', 'activity_date');
        $this->convertColumn('instructor_research_records', 'acceptance_date');
        $this->convertColumn('yearly_standard_results');
        $this->convertColumn('calculation_logs');
        $this->convertColumn('hour_exchange_records');
        $this->convertColumn('instructor_norm_reductions', 'start_date');
        $this->convertColumn('department_overtime_pools');
    }

    public function down(): void
    {
        // The previous migration owns the reversible schema contract.
    }

    private function convertColumn(string $table, ?string $dateColumn = null): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'academic_year')) {
            return;
        }

        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $fallback = $sqlite
            ? "CAST(substr(`academic_year`, 1, 4) AS INTEGER)"
            : "CAST(LEFT(`academic_year`, 4) AS UNSIGNED)";
        $value = $dateColumn && Schema::hasColumn($table, $dateColumn)
            ? ($sqlite ? "COALESCE(CAST(strftime('%Y', `{$dateColumn}`) AS INTEGER), {$fallback})" : "COALESCE(YEAR(`{$dateColumn}`), {$fallback})")
            : $fallback;

        DB::statement("UPDATE `{$table}` SET `academic_year` = {$value}");
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('academic_year', 'year'));
        if (! $sqlite) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `year` SMALLINT UNSIGNED NOT NULL");
        }
    }
};
