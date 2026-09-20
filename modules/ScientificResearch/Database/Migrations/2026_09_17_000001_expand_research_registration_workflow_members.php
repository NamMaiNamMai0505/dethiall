<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            if (! Schema::hasColumn('scientific_research_registrations', 'academic_year')) {
                $table->string('academic_year', 30)->nullable()->after('topic');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'implementation_year')) {
                $table->unsignedSmallInteger('implementation_year')->nullable()->after('academic_year');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'duration_years')) {
                $table->decimal('duration_years', 5, 2)->default(1)->after('implementation_year');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'product_quantity')) {
                $table->unsignedInteger('product_quantity')->default(1)->after('budget');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'lead_contribution_percent')) {
                $table->decimal('lead_contribution_percent', 5, 2)->default(100)->after('participant_count');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'unit_review_note')) {
                $table->text('unit_review_note')->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'unit_reviewed_by')) {
                $table->foreignId('unit_reviewed_by')->nullable()->after('unit_review_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'unit_reviewed_at')) {
                $table->timestamp('unit_reviewed_at')->nullable()->after('unit_reviewed_by');
            }
        });

        Schema::create('scientific_research_registration_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->constrained('scientific_research_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('unit_name')->nullable();
            $table->string('role')->default('Thành viên');
            $table->decimal('contribution_percent', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_research_registration_members');

        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            foreach (['unit_reviewed_by'] as $column) {
                if (Schema::hasColumn('scientific_research_registrations', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'academic_year',
                'implementation_year',
                'duration_years',
                'product_quantity',
                'lead_contribution_percent',
                'unit_review_note',
                'unit_reviewed_at',
            ] as $column) {
                if (Schema::hasColumn('scientific_research_registrations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
