<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_external_activity_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->restrictOnDelete();
            $table->string('activity_type', 50)->default('other');
            $table->string('activity_name');
            $table->text('activity_details')->nullable();
            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('period_mode', 24)->default('calendar_year');
            $table->string('role_or_position')->nullable();
            $table->string('organizer')->nullable();
            $table->string('location')->nullable();
            $table->text('result')->nullable();
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('review_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_mode', 'year'], 'external_activities_period_idx');
            $table->index(['instructor_id', 'status'], 'external_activities_owner_status_idx');
            $table->index('from_date');
            $table->index('activity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_external_activity_records');
    }
};
