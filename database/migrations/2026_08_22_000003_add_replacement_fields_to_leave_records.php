<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('leave_records', 'replacement_personnel_id')) {
                $table->foreignId('replacement_personnel_id')->nullable()->constrained('leave_personnel')->nullOnDelete();
            }
            if (! Schema::hasColumn('leave_records', 'replacement_personnel_name')) {
                $table->string('replacement_personnel_name')->nullable();
            }
            if (! Schema::hasColumn('leave_records', 'replacement_position')) {
                $table->string('replacement_position')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_records', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('leave_records', 'replacement_personnel_id') ? 'replacement_personnel_id' : null,
                Schema::hasColumn('leave_records', 'replacement_personnel_name') ? 'replacement_personnel_name' : null,
                Schema::hasColumn('leave_records', 'replacement_position') ? 'replacement_position' : null,
            ]));
            if (in_array('replacement_personnel_id', $columns, true)) {
                $table->dropForeign(['replacement_personnel_id']);
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
