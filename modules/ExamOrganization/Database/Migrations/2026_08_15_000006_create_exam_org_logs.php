<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_organization_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('exam_organization_plans')->cascadeOnDelete();
            $table->string('process_type', 40);
            $table->string('method', 80)->nullable();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('file_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['plan_id', 'process_type']);
        });
    }

    public function down(): void { Schema::dropIfExists('exam_organization_logs'); }
};
