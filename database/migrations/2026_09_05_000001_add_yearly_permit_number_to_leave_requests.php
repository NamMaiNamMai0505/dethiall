<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('leave_requests', 'permit_year')) {
                $table->unsignedSmallInteger('permit_year')->nullable()->after('printed_at');
            }
            if (! Schema::hasColumn('leave_requests', 'permit_number')) {
                $table->unsignedInteger('permit_number')->nullable()->after('permit_year');
            }
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->unique(['permit_year', 'permit_number'], 'leave_requests_permit_year_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropUnique('leave_requests_permit_year_number_unique');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['permit_year', 'permit_number'],
                fn (string $column): bool => Schema::hasColumn('leave_requests', $column)
            ));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
