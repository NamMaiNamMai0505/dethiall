<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('essay_exams', function (Blueprint $table): void {
            if (! Schema::hasColumn('essay_exams', 'source_document_path')) {
                $table->string('source_document_path')->nullable()->after('approval_qr');
            }
            if (! Schema::hasColumn('essay_exams', 'source_pdf_path')) {
                $table->string('source_pdf_path')->nullable()->after('source_document_path');
            }
            if (! Schema::hasColumn('essay_exams', 'source_original_name')) {
                $table->string('source_original_name')->nullable()->after('source_pdf_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('essay_exams', function (Blueprint $table): void {
            foreach (['source_original_name', 'source_pdf_path', 'source_document_path'] as $column) {
                if (Schema::hasColumn('essay_exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
