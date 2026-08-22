<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('export_template_versions') && Schema::hasColumn('export_template_versions', 'file_path')) {
            Schema::table('export_template_versions', function (Blueprint $table) {
                $table->string('file_path')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('export_template_versions') && Schema::hasColumn('export_template_versions', 'file_path')) {
            Schema::table('export_template_versions', function (Blueprint $table) {
                $table->string('file_path')->nullable(false)->change();
            });
        }
    }
};
