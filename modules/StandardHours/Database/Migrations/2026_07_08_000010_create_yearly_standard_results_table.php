<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yearly_standard_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->restrictOnDelete();
            $table->string('academic_year', 20);
            $table->foreignId('object_type_id')->nullable()->constrained('standard_object_types')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('standard_positions')->nullOnDelete();
            $table->decimal('teaching_hours', 10, 2)->default(0);
            $table->decimal('conversion_hours', 10, 2)->default(0);
            $table->decimal('research_hours', 10, 2)->default(0);
            $table->decimal('total_standard_hours', 10, 2)->default(0);
            $table->decimal('standard_norm_hours', 10, 2)->default(0);
            $table->decimal('standard_difference', 10, 2)->default(0);
            $table->decimal('min_classroom_hours', 10, 2)->default(0);
            $table->boolean('meets_standard')->default(false);
            $table->boolean('meets_classroom')->default(false);
            $table->decimal('research_norm_hours', 10, 2)->default(0);
            $table->decimal('research_difference', 10, 2)->default(0);
            $table->boolean('meets_research')->default(false);
            $table->boolean('meets_overall')->default(false);
            $table->enum('status', ['calculated', 'locked'])->default('calculated');
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['instructor_id', 'academic_year']);
            $table->index('academic_year');
            $table->index('status');
            $table->index('meets_overall');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yearly_standard_results');
    }
};
