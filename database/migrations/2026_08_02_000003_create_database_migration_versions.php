<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('database_migration_versions')) {
            return;
        }

        Schema::create('database_migration_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version');
            $table->enum('status', ['draft', 'validated', 'published', 'rejected'])->default('draft');
            $table->longText('up_sql');
            $table->longText('down_sql')->nullable();
            $table->string('checksum', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['name', 'version']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_migration_versions');
    }
};
