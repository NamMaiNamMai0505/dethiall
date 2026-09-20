<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scientific_research_plans') || Schema::hasColumn('scientific_research_plans', 'registration_id')) {
            return;
        }

        Schema::table('scientific_research_plans', function (Blueprint $table): void {
            $table->foreignId('registration_id')
                ->nullable()
                ->after('id')
                ->constrained('scientific_research_registrations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scientific_research_plans') || ! Schema::hasColumn('scientific_research_plans', 'registration_id')) {
            return;
        }

        Schema::table('scientific_research_plans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('registration_id');
        });
    }
};
