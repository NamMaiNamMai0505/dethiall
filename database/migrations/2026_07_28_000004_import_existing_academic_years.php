<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years')) {
            return;
        }

        $sources = [
            ['training_schedules', 'academic_year'],
            ['grade_books', 'academic_year'],
            ['grade_graduation_sessions', 'academic_year'],
            ['grade_conduct_records', 'academic_year'],
        ];

        $codes = collect();
        foreach ($sources as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $codes = $codes->merge(
                DB::table($table)->whereNotNull($column)->distinct()->pluck($column)
            );
        }

        $now = now();
        foreach ($codes->filter()->unique() as $code) {
            if (! preg_match('/^(\d{4})-(\d{4})$/', (string) $code, $matches)) {
                continue;
            }
            $start = (int) $matches[1];
            $end = (int) $matches[2];
            if ($end !== $start + 1) {
                continue;
            }

            DB::table('academic_years')->insertOrIgnore([
                'code' => $code,
                'start_year' => $start,
                'end_year' => $end,
                'name' => 'Năm học '.$code,
                'starts_at' => $start.'-08-01',
                'ends_at' => $end.'-07-31',
                'is_current' => false,
                'is_active' => true,
                'sort_order' => $start,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Không xóa danh mục vì các module có thể đã tham chiếu mã năm học này.
    }
};
