<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERIOD_TABLES = [
        'standard_hour_norms',
        'research_hour_norms',
        'instructor_conversion_records',
        'instructor_research_records',
        'yearly_standard_results',
        'calculation_logs',
        'hour_exchange_records',
        'instructor_norm_reductions',
        'department_overtime_pools',
    ];

    public function up(): void
    {
        foreach (self::PERIOD_TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'period_mode')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('period_mode', 24)
                    ->default('calendar_year')
                    ->after('year')
                    ->index();
            });
        }

        $this->replaceUnique(
            'standard_hour_norms',
            ['object_type_id', 'position_id', 'year'],
            ['object_type_id', 'position_id', 'year', 'period_mode'],
            'hour_norms_period_unique'
        );
        $this->replaceUnique(
            'research_hour_norms',
            ['object_type_id', 'year'],
            ['object_type_id', 'year', 'period_mode'],
            'research_hour_norms_period_unique'
        );
        $this->replaceUnique(
            'yearly_standard_results',
            ['instructor_id', 'year'],
            ['instructor_id', 'year', 'period_mode'],
            'yearly_results_period_unique'
        );
        $this->replaceUnique(
            'department_overtime_pools',
            ['department_id', 'year'],
            ['department_id', 'year', 'period_mode'],
            'department_overtime_period_unique'
        );

        if (Schema::hasTable('yearly_standard_results')) {
            Schema::table('yearly_standard_results', function (Blueprint $table) {
                if (! Schema::hasColumn('yearly_standard_results', 'declaration_status')) {
                    $table->string('declaration_status', 20)
                        ->nullable()
                        ->after('declared_at')
                        ->index();
                    $table->foreignId('declaration_approved_by')
                        ->nullable()
                        ->after('declaration_status')
                        ->constrained('users')
                        ->nullOnDelete();
                    $table->timestamp('declaration_approved_at')
                        ->nullable()
                        ->after('declaration_approved_by');
                    $table->text('declaration_review_note')
                        ->nullable()
                        ->after('declaration_approved_at');
                }

                if (! Schema::hasColumn('yearly_standard_results', 'overtime_eligible_hours')) {
                    $table->decimal('overtime_eligible_hours', 10, 2)
                        ->default(0)
                        ->after('total_standard_hours');
                }
            });

            DB::table('yearly_standard_results')
                ->whereNotNull('declared_at')
                ->whereNull('declaration_status')
                ->update([
                    'declaration_status' => 'approved',
                    'declaration_approved_at' => DB::raw('declared_at'),
                ]);
        }

        if (Schema::hasTable('instructor_conversion_records')) {
            Schema::table('instructor_conversion_records', function (Blueprint $table) {
                if (! Schema::hasColumn('instructor_conversion_records', 'has_other_remuneration')) {
                    $table->boolean('has_other_remuneration')
                        ->default(false)
                        ->after('converted_hours');
                }
                if (! Schema::hasColumn('instructor_conversion_records', 'is_external_invitation')) {
                    $table->boolean('is_external_invitation')
                        ->default(false)
                        ->after('has_other_remuneration');
                }
            });
        }

        if (
            Schema::hasTable('schedule_details')
            && Schema::hasTable('conversion_categories')
            && ! Schema::hasColumn('schedule_details', 'standard_hours_conversion_category_id')
        ) {
            Schema::table('schedule_details', function (Blueprint $table) {
                $table->foreignId('standard_hours_conversion_category_id')
                    ->nullable()
                    ->after('lesson_type')
                    ->constrained('conversion_categories')
                    ->nullOnDelete();
            });
        }

        // Thông tư nội bộ: Chủ nhiệm/Trưởng khoa áp dụng 60% định mức.
        if (Schema::hasTable('standard_positions')) {
            DB::table('standard_positions')
                ->whereIn('name', ['Chủ nhiệm khoa', 'Trưởng khoa'])
                ->update([
                    'ratio_percent' => 60,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('schedule_details')
            && Schema::hasColumn('schedule_details', 'standard_hours_conversion_category_id')
        ) {
            Schema::table('schedule_details', function (Blueprint $table) {
                $table->dropConstrainedForeignId('standard_hours_conversion_category_id');
            });
        }

        if (Schema::hasTable('instructor_conversion_records')) {
            Schema::table('instructor_conversion_records', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['has_other_remuneration', 'is_external_invitation'],
                    fn (string $column) => Schema::hasColumn('instructor_conversion_records', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('yearly_standard_results')) {
            Schema::table('yearly_standard_results', function (Blueprint $table) {
                if (Schema::hasColumn('yearly_standard_results', 'declaration_approved_by')) {
                    $table->dropConstrainedForeignId('declaration_approved_by');
                }

                $columns = array_values(array_filter(
                    [
                        'declaration_status',
                        'declaration_approved_at',
                        'declaration_review_note',
                        'overtime_eligible_hours',
                    ],
                    fn (string $column) => Schema::hasColumn('yearly_standard_results', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        $this->replaceUnique(
            'standard_hour_norms',
            ['object_type_id', 'position_id', 'year', 'period_mode'],
            ['object_type_id', 'position_id', 'year'],
            'hour_norms_unique'
        );
        $this->replaceUnique(
            'research_hour_norms',
            ['object_type_id', 'year', 'period_mode'],
            ['object_type_id', 'year'],
            'research_hour_norms_unique'
        );
        $this->replaceUnique(
            'yearly_standard_results',
            ['instructor_id', 'year', 'period_mode'],
            ['instructor_id', 'year'],
            'yearly_standard_results_instructor_year_unique'
        );
        $this->replaceUnique(
            'department_overtime_pools',
            ['department_id', 'year', 'period_mode'],
            ['department_id', 'year'],
            'dept_ot_pool_uq'
        );

        foreach (array_reverse(self::PERIOD_TABLES) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'period_mode')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('period_mode'));
            }
        }
    }

    /**
     * Replace a unique index by matching its columns instead of assuming the
     * legacy index name (renaming academic_year to year does not rename indexes).
     */
    private function replaceUnique(
        string $tableName,
        array $oldColumns,
        array $newColumns,
        string $newName
    ): void {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $indexes = Schema::getIndexes($tableName);
        $oldIndexName = null;
        foreach ($indexes as $index) {
            $columns = array_map('strtolower', $index['columns'] ?? []);
            $expected = array_map('strtolower', $oldColumns);
            if (($index['unique'] ?? false) && $columns === $expected) {
                $oldIndexName = $index['name'];
                break;
            }
        }

        $alreadyExists = collect($indexes)->contains(
            fn (array $index) => ($index['unique'] ?? false)
                && array_map('strtolower', $index['columns'] ?? [])
                    === array_map('strtolower', $newColumns)
        );

        if (! $alreadyExists) {
            Schema::table(
                $tableName,
                fn (Blueprint $table) => $table->unique($newColumns, $newName)
            );
        }

        // MySQL may use the old unique index as the backing index for an FK.
        // Create the replacement first so dropping the legacy index remains valid.
        if ($oldIndexName !== null) {
            Schema::table(
                $tableName,
                fn (Blueprint $table) => $table->dropUnique($oldIndexName)
            );
        }
    }
};
