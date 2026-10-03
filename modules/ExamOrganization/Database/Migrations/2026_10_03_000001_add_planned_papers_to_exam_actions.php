<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_organization_actions') && ! Schema::hasColumn('exam_organization_actions', 'planned_papers')) {
            Schema::table('exam_organization_actions', function (Blueprint $table): void {
                $table->unsignedInteger('planned_papers')->nullable()->after('converted_hours');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_organization_actions') && Schema::hasColumn('exam_organization_actions', 'planned_papers')) {
            Schema::table('exam_organization_actions', function (Blueprint $table): void {
                $table->dropColumn('planned_papers');
            });
        }
    }
};
