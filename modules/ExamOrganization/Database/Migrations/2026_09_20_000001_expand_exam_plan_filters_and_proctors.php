<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_organization_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_organization_plans', 'training_system_id')) {
                $table->foreignId('training_system_id')->nullable()->after('custom_exam_name')->constrained('training_systems')->nullOnDelete();
            }
            if (! Schema::hasColumn('exam_organization_plans', 'specialization_id')) {
                $table->foreignId('specialization_id')->nullable()->after('training_system_id')->constrained('specializations')->nullOnDelete();
            }
            if (! Schema::hasColumn('exam_organization_plans', 'exam_attempt')) {
                $table->unsignedTinyInteger('exam_attempt')->default(1)->after('exam_category');
            }
        });

        Schema::table('exam_organization_actions', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_organization_actions', 'conversion_category_id')) {
                $table->foreignId('conversion_category_id')->nullable()->after('instructor_id')->constrained('conversion_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('exam_organization_actions', 'converted_hours')) {
                $table->decimal('converted_hours', 8, 2)->nullable()->after('conversion_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_organization_actions', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_organization_actions', 'conversion_category_id')) {
                $table->dropConstrainedForeignId('conversion_category_id');
            }
            if (Schema::hasColumn('exam_organization_actions', 'converted_hours')) {
                $table->dropColumn('converted_hours');
            }
        });

        Schema::table('exam_organization_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_organization_plans', 'training_system_id')) {
                $table->dropConstrainedForeignId('training_system_id');
            }
            if (Schema::hasColumn('exam_organization_plans', 'specialization_id')) {
                $table->dropConstrainedForeignId('specialization_id');
            }
            if (Schema::hasColumn('exam_organization_plans', 'exam_attempt')) {
                $table->dropColumn('exam_attempt');
            }
        });
    }
};
