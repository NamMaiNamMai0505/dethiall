<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_personnel', 'position_id')) {
            Schema::table('leave_personnel', fn (Blueprint $table) => $table->foreignId('position_id')->nullable()->after('position')->constrained('standard_positions')->nullOnDelete());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_personnel', 'position_id')) {
            Schema::table('leave_personnel', function (Blueprint $table): void {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            });
        }
    }
};
