<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Giờ chuẩn được chốt theo năm dương lịch. Năm học vẫn thuộc lịch đào tạo/LMS/điểm.
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

        if (Schema::hasTable('conversion_categories') && ! Schema::hasColumn('conversion_categories', 'entry_source')) {
            Schema::table('conversion_categories', function (Blueprint $table) {
                $table->string('entry_source', 20)
                    ->default('manual')
                    ->after('description')
                    ->index();
            });

            DB::table('conversion_categories')
                ->whereIn('code', ['HD-01', 'HD-02', 'HD-03', 'HD-04', 'HD-05', 'HD-06', 'HD-07', 'HD-08'])
                ->update(['entry_source' => 'schedule']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('conversion_categories') && Schema::hasColumn('conversion_categories', 'entry_source')) {
            Schema::table('conversion_categories', function (Blueprint $table) {
                $table->dropColumn('entry_source');
            });
        }

        foreach ([
            'standard_hour_norms',
            'research_hour_norms',
            'instructor_conversion_records',
            'instructor_research_records',
            'yearly_standard_results',
            'calculation_logs',
            'hour_exchange_records',
            'instructor_norm_reductions',
            'department_overtime_pools',
        ] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'year')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY `year` VARCHAR(20) NOT NULL");
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('year', 'academic_year'));
            DB::statement("UPDATE `{$table}` SET `academic_year` = CONCAT(`academic_year`, '-', CAST(`academic_year` AS UNSIGNED) + 1)");
        }
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
