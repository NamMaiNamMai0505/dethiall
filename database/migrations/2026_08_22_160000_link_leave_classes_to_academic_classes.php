<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_classes', function (Blueprint $table): void {
            $table->foreignId('source_class_id')->nullable()->unique()->after('id')->constrained('classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_classes', function (Blueprint $table): void {
            $table->dropForeign(['source_class_id']);
            $table->dropUnique(['source_class_id']);
            $table->dropColumn('source_class_id');
        });
    }
};
