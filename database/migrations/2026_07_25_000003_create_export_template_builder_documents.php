<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('export_template_builder_documents')) {
            return;
        }

        Schema::create('export_template_builder_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_version_id')
                ->unique()
                ->constrained('export_template_versions')
                ->cascadeOnDelete();
            $table->json('schema');
            $table->string('schema_version', 16)->default('1.0');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_template_builder_documents');
    }
};
