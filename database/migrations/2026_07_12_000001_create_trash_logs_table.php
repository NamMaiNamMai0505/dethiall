<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trash_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 64)->index();
            $table->string('type_label', 120);
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('title');
            $table->string('identifier')->nullable();
            $table->json('summary')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->index();
            $table->timestamp('restored_at')->nullable()->index();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['module_key', 'restored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trash_logs');
    }
};
