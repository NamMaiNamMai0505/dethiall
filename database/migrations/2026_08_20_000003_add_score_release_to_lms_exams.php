<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lms_exams', function (Blueprint $table): void {
            $table->boolean('publish_score_after_submit')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('lms_exams', function (Blueprint $table): void {
            $table->dropColumn('publish_score_after_submit');
        });
    }
};
