<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_organization_candidates', function (Blueprint $table): void {
            $table->decimal('score', 5, 2)->nullable()->after('cipher_number');
            $table->string('score_method', 30)->nullable()->after('score');
        });
    }
    public function down(): void { Schema::table('exam_organization_candidates', fn (Blueprint $table) => $table->dropColumn(['score','score_method'])); }
};
