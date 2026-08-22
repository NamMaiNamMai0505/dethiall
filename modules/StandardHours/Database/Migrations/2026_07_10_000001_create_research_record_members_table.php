<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_record_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_record_id')->constrained('instructor_research_records')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('instructors')->restrictOnDelete();
            $table->string('role')->nullable();
            $table->enum('participation_type', ['lead', 'member'])->default('member');
            $table->decimal('contribution_percent', 5, 2)->nullable();
            $table->decimal('converted_hours', 10, 2)->default(0);
            $table->boolean('is_declarant')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['research_record_id', 'instructor_id']);
            $table->index(['instructor_id', 'research_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_record_members');
    }
};
