<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_research_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->restrictOnDelete();
            $table->foreignId('research_category_id')->constrained('research_categories')->restrictOnDelete();
            $table->string('product_name');
            $table->string('role')->nullable();
            $table->enum('participation_type', ['lead', 'member'])->default('lead');
            $table->date('publication_date')->nullable();
            $table->date('acceptance_date');
            $table->string('academic_year', 20);
            $table->unsignedSmallInteger('member_count')->default(1);
            $table->unsignedSmallInteger('duration_years')->default(1);
            $table->decimal('converted_hours', 10, 2);
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
            $table->index('acceptance_date');
            $table->index('academic_year');
            $table->index('status');
            $table->index('research_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_research_records');
    }
};
