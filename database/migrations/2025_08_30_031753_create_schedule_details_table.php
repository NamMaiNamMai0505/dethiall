<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('schedule_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_schedule_id')->comment('FK tới bảng training_schedules');
            $table->date('date')->comment('Ngày học');
            $table->integer('period')->comment('Tiết học 1,2,3,...');
            $table->unsignedBigInteger('subject_id')->comment('FK tới môn học');
            $table->unsignedBigInteger('instructor_id')->comment('FK tới giảng viên');
            $table->unsignedBigInteger('classroom_id')->comment('FK tới phòng học');

            $table->timestamps();

            // Foreign keys
            $table->foreign('training_schedule_id')->references('id')->on('training_schedules')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('restrict');
            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('restrict');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('restrict');

            // Index phục vụ check conflict
            $table->unique(['date', 'period', 'instructor_id'], 'uq_instructor_period');
            $table->unique(['date', 'period', 'classroom_id'], 'uq_classroom_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_details');
    }
};
