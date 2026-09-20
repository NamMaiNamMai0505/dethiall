<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            if (! Schema::hasColumn('scientific_research_registrations', 'standard_hours_research_record_id')) {
                $table->foreignId('standard_hours_research_record_id')
                    ->nullable()
                    ->after('extended_until')
                    ->constrained('instructor_research_records')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            if (Schema::hasColumn('scientific_research_registrations', 'standard_hours_research_record_id')) {
                $table->dropConstrainedForeignId('standard_hours_research_record_id');
            }
        });
    }
};
