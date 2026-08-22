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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('specialization_id')->constrained('specializations');
            $table->foreignId('instructor_id')->constrained('instructors');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('duration_months');
            $table->string('management_unit');
            $table->string('classroom');
            $table->integer('max_students')->default(30);
            $table->integer('current_students')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'specialization_id']);
            $table->index(['is_active', 'instructor_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
