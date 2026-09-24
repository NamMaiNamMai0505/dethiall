<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('essay_exam_paper_codes')) return;

        DB::table('essay_exam_paper_codes')
            ->join('essay_exams', 'essay_exams.id', '=', 'essay_exam_paper_codes.essay_exam_id')
            ->select('essay_exam_paper_codes.id', 'essay_exam_paper_codes.paper_number', 'essay_exams.code as exam_code')
            ->orderBy('essay_exam_paper_codes.id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('essay_exam_paper_codes')->where('id', $row->id)->update([
                        'code' => $row->exam_code.'-D'.str_pad((string) $row->paper_number, 2, '0', STR_PAD_LEFT),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // The original paper code format is shared with printed exam records.
    }
};
