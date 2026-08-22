<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_organization_logs', function (Blueprint $table): void {
            $table->index(['process_type', 'created_at'], 'exam_org_logs_process_created_idx');
            $table->index(['plan_id', 'process_type'], 'exam_org_logs_plan_process_idx');
        });
        Schema::table('exam_organization_candidates', function (Blueprint $table): void {
            $table->index(['plan_id', 'packet_number'], 'exam_org_candidates_plan_packet_idx');
            $table->index(['plan_id', 'candidate_number'], 'exam_org_candidates_plan_number_idx');
        });
    }

    public function down(): void
    {
        Schema::table('exam_organization_logs', function (Blueprint $table): void {
            $table->dropIndex('exam_org_logs_process_created_idx');
            $table->dropIndex('exam_org_logs_plan_process_idx');
        });
        Schema::table('exam_organization_candidates', function (Blueprint $table): void {
            $table->dropIndex('exam_org_candidates_plan_packet_idx');
            $table->dropIndex('exam_org_candidates_plan_number_idx');
        });
    }
};
