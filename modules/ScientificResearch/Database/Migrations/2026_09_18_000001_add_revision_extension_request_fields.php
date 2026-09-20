<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            if (! Schema::hasColumn('scientific_research_registrations', 'revision_response_note')) {
                $table->text('revision_response_note')->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'revision_submitted_at')) {
                $table->timestamp('revision_submitted_at')->nullable()->after('revision_response_note');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_requested_until')) {
                $table->date('extension_requested_until')->nullable()->after('extended_until');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_request_note')) {
                $table->text('extension_request_note')->nullable()->after('extension_requested_until');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_previous_status')) {
                $table->string('extension_previous_status', 30)->nullable()->after('extension_request_note');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_requested_at')) {
                $table->timestamp('extension_requested_at')->nullable()->after('extension_previous_status');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_review_note')) {
                $table->text('extension_review_note')->nullable()->after('extension_requested_at');
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_reviewed_by')) {
                $table->foreignId('extension_reviewed_by')->nullable()->after('extension_review_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('scientific_research_registrations', 'extension_reviewed_at')) {
                $table->timestamp('extension_reviewed_at')->nullable()->after('extension_reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scientific_research_registrations', function (Blueprint $table): void {
            if (Schema::hasColumn('scientific_research_registrations', 'extension_reviewed_by')) {
                $table->dropForeign(['extension_reviewed_by']);
            }

            foreach ([
                'extension_reviewed_by',
                'extension_reviewed_at',
                'extension_review_note',
                'extension_requested_at',
                'extension_previous_status',
                'extension_request_note',
                'extension_requested_until',
                'revision_submitted_at',
                'revision_response_note',
            ] as $column) {
                if (Schema::hasColumn('scientific_research_registrations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
