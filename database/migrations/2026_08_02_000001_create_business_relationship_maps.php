<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_relationship_maps')) {
            return;
        }

        Schema::create('business_relationship_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('module_key', 80);
            $table->string('source_table', 120);
            $table->string('source_field', 120);
            $table->string('target_table', 120);
            $table->string('target_field', 120);
            $table->enum('relationship_type', ['one_to_one', 'one_to_many', 'many_to_many'])->default('one_to_many');
            $table->enum('status', ['proposed', 'active', 'archived'])->default('proposed');
            $table->json('rules')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['module_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_relationship_maps');
    }
};
