<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_organization_plans', function (Blueprint $table): void {
            $table->string('exam_category', 40)->default('REGULAR')->after('name');
            $table->string('custom_exam_name')->nullable()->after('exam_category');
            $table->string('exam_type', 30)->default('TỰ LUẬN')->after('exam_form');
        });
    }

    public function down(): void
    {
        Schema::table('exam_organization_plans', function (Blueprint $table): void {
            $table->dropColumn(['exam_category', 'custom_exam_name', 'exam_type']);
        });
    }
};
