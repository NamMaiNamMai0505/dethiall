<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_organization_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('exam_organization_plans')->cascadeOnDelete();
            $table->string('student_code')->nullable();
            $table->string('student_name');
            $table->string('class_name')->nullable();
            $table->string('candidate_number')->nullable();
            $table->string('room_name')->nullable();
            $table->string('seat_number')->nullable();
            $table->string('cipher_number')->nullable();
            $table->boolean('absent')->default(false);
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();
            $table->index(['plan_id', 'candidate_number']);
        });
    }

    public function down(): void { Schema::dropIfExists('exam_organization_candidates'); }
};
