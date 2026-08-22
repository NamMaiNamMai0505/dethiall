<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hour_exchange_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->restrictOnDelete();
            $table->string('academic_year', 20);
            $table->enum('direction', ['nckh_to_cm', 'cm_to_nckh']);
            $table->decimal('source_hours', 10, 2);
            $table->decimal('target_hours', 10, 2);
            $table->decimal('rate', 8, 4)->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['instructor_id', 'academic_year']);
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_exchange_records');
    }
};
