<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('export_templates') && Schema::hasColumn('export_templates', 'file_path')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->string('file_path')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('export_templates') && Schema::hasColumn('export_templates', 'file_path')) {
            Schema::table('export_templates', function (Blueprint $table) {
                $table->string('file_path')->nullable(false)->change();
            });
        }
    }
};
