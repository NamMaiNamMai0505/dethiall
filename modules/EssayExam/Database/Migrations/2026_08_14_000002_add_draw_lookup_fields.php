<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('essay_exam_draws', function (Blueprint $table): void {
            $table->string('qr_code')->nullable()->unique()->after('draw_code');
            $table->timestamp('printed_at')->nullable()->after('drawn_at');
        });
    }

    public function down(): void
    {
        Schema::table('essay_exam_draws', function (Blueprint $table): void {
            $table->dropColumn(['qr_code', 'printed_at']);
        });
    }
};
