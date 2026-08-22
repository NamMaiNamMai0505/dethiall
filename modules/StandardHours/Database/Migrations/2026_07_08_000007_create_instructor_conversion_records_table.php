<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_conversion_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->restrictOnDelete();
            $table->foreignId('conversion_category_id')->constrained('conversion_categories')->restrictOnDelete();
            $table->string('activity_name');
            $table->date('activity_date');
            $table->string('academic_year', 20);
            $table->decimal('quantity', 8, 2);
            $table->decimal('converted_hours', 8, 2);
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('instructor_id');
            $table->index('activity_date');
            $table->index('academic_year');
            $table->index('status');
            $table->index('conversion_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_conversion_records');
    }
};
