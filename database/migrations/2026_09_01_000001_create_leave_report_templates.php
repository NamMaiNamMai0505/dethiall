<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('leave_report_templates')) {
            return;
        }

        Schema::create('leave_report_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('report_type', 40)->index();
            $table->text('description')->nullable();
            $table->string('disk', 40)->default('local');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_report_templates');
    }
};
