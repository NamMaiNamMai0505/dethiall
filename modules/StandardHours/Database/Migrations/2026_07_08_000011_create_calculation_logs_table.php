<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->enum('action', ['preview', 'calculate', 'rollback', 'lock']);
            $table->unsignedInteger('instructors_processed')->default(0);
            $table->unsignedInteger('instructors_skipped')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('academic_year');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculation_logs');
    }
};
