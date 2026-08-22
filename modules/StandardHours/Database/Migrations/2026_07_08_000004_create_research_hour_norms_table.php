<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_hour_norms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_type_id')->constrained('standard_object_types')->restrictOnDelete();
            $table->string('academic_year', 20);
            $table->decimal('research_hours', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['object_type_id', 'academic_year'], 'research_hour_norms_unique');
            $table->index('academic_year');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_hour_norms');
    }
};
