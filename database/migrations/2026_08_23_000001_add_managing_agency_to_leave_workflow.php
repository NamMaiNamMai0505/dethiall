<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['leave_personnel','leave_requests'] as $table) {
            if (!Schema::hasColumn($table, 'managing_agency')) Schema::table($table, fn(Blueprint $t) => $t->string('managing_agency', 30)->default('QUAN_LUC')->index());
            DB::table($table)->whereNotIn('managing_agency', ['QUAN_LUC','CO_QUAN_QUAN_LY'])->update(['managing_agency' => 'QUAN_LUC']);
        }
    }
    public function down(): void {
        foreach (['leave_personnel','leave_requests'] as $table) if (Schema::hasColumn($table, 'managing_agency')) Schema::table($table, fn(Blueprint $t) => $t->dropColumn('managing_agency'));
    }
};
