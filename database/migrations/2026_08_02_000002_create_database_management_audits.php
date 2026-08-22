<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('database_management_audits')) {
            return;
        }

        Schema::create('database_management_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('table_name', 120);
            $table->string('record_key', 160)->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->string('request_id', 80)->nullable();
            $table->timestamps();
            $table->index(['table_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_management_audits');
    }
};
