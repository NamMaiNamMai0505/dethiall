<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scientific_research_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 80);
            $table->string('target_type', 120)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('title')->nullable();
            $table->json('changes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['target_type', 'target_id'], 'sci_research_audit_target_idx');
            $table->index(['action', 'created_at'], 'sci_research_audit_action_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_research_audit_logs');
    }
};
