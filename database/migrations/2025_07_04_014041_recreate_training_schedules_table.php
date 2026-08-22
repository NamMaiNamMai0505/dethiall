<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('training_schedules', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('name');
            $table->string('code', 50)->unique();

            // Academic information
            $table->foreignId('specialization_id')->nullable()->constrained('specializations');
            $table->foreignId('class_id')->nullable()->constrained('classes');
            $table->string('class_code')->nullable();
            $table->string('academic_year', 9); // Format: "2024-2025"
            $table->enum('semester', ['semester_1', 'semester_2', 'semester_3',
                'semester_4', 'semester_5', 'semester_6', 'summer']);

            // Date range
            $table->date('start_date');
            $table->date('end_date');

            // Weekly schedule (stores weeks with daily details)
            $table->json('weekly_schedule')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Essential indexes only
            $table->index(['specialization_id', 'academic_year', 'semester'], 'ts_spec_year_semester_idx');

            $table->foreign('class_code')
                ->references('code')
                ->on('classes')
                ->nullOnDelete();

            $table->index('is_active', 'ts_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
