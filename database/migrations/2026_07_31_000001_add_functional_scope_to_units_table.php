<?php

use App\Support\ApprovalAgency;
use App\Support\TrainingDept;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Unit\Models\Unit;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('units')) {
            return;
        }

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'functional_type')) {
                $table->string('functional_type', 32)
                    ->default(Unit::FUNCTIONAL_OTHER)
                    ->after('level')
                    ->index('units_functional_type_index');
            }

            if (! Schema::hasColumn('units', 'faculty_code')) {
                $table->string('faculty_code', 16)
                    ->nullable()
                    ->after('functional_type')
                    ->index('units_faculty_code_index');
            }
        });

        DB::table('units')
            ->whereRaw('UPPER(code) = ?', [TrainingDept::UNIT_CODE_TRAINING_OFFICE])
            ->update(['functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE]);

        foreach (TrainingDept::FACULTY_CODES as $facultyCode) {
            DB::table('units')
                ->whereRaw('UPPER(code) = ?', [$facultyCode])
                ->update([
                    'functional_type' => Unit::FUNCTIONAL_FACULTY,
                    'faculty_code' => $facultyCode,
                ]);
        }

        foreach ([ApprovalAgency::RESEARCH_UNIT_CODE, ApprovalAgency::STANDARD_HOURS_GRADES_UNIT_CODE] as $unitCode) {
            DB::table('units')
                ->whereRaw('UPPER(code) = ?', [mb_strtoupper($unitCode)])
                ->update(['functional_type' => Unit::FUNCTIONAL_APPROVAL_AGENCY]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('units')) {
            return;
        }

        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'faculty_code')) {
                $table->dropIndex('units_faculty_code_index');
                $table->dropColumn('faculty_code');
            }

            if (Schema::hasColumn('units', 'functional_type')) {
                $table->dropIndex('units_functional_type_index');
                $table->dropColumn('functional_type');
            }
        });
    }
};
