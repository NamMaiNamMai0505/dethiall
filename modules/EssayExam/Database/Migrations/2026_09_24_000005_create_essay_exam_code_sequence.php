<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('essay_exam_code_sequences')) {
            Schema::create('essay_exam_code_sequences', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->unsignedSmallInteger('last_number');
            });
        }

        $lastNumber = DB::table('essay_exams')->where('code', 'like', 'TL____')->pluck('code')
            ->filter(fn ($code) => preg_match('/^TL\d{4}$/', $code) === 1)
            ->map(fn ($code) => (int) substr($code, 2))
            ->max() ?? 0;
        $current = DB::table('essay_exam_code_sequences')->where('id', 1)->value('last_number');
        DB::table('essay_exam_code_sequences')->updateOrInsert(
            ['id' => 1],
            ['last_number' => max((int) $current, $lastNumber)]
        );
    }

    public function down(): void
    {
        // Keep the sequence so a rollback cannot reuse a code already issued.
    }
};
