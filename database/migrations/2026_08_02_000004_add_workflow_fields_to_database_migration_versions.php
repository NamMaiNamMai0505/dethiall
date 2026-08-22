<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('database_migration_versions')) return;
        Schema::table('database_migration_versions', function (Blueprint $table): void {
            if (! Schema::hasColumn('database_migration_versions', 'validation_report')) $table->json('validation_report')->nullable()->after('checksum');
            if (! Schema::hasColumn('database_migration_versions', 'backup_reference')) $table->string('backup_reference')->nullable()->after('validation_report');
            if (! Schema::hasColumn('database_migration_versions', 'rollback_status')) $table->string('rollback_status', 30)->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('database_migration_versions')) return;
        Schema::table('database_migration_versions', function (Blueprint $table): void {
            foreach (['validation_report', 'backup_reference', 'rollback_status'] as $column) {
                if (Schema::hasColumn('database_migration_versions', $column)) $table->dropColumn($column);
            }
        });
    }
};
