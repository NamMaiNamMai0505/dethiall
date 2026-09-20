<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            if (! Schema::hasColumn('scientific_research_registrations', 'project_code')) {
                $table->string('project_code')->nullable()->after('announcement_id');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'progress_percent')) {
                $table->unsignedTinyInteger('progress_percent')->default(0)->after('status');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extended_until')) {
                $table->date('extended_until')->nullable()->after('progress_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            foreach (['project_code', 'progress_percent', 'extended_until'] as $column) {
                if (Schema::hasColumn('scientific_research_registrations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
