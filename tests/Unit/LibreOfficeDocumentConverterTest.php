<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Services\LibreOfficeDocumentConverter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LibreOfficeDocumentConverterTest extends TestCase
{
    public function test_converter_supports_office_to_pdf_only(): void
    {
        $converter = app(LibreOfficeDocumentConverter::class);

        $this->assertTrue($converter->supports('docx', 'pdf'));
        $this->assertTrue($converter->supports('.xlsx', '.pdf'));
        $this->assertFalse($converter->supports('pdf', 'docx'));
        $this->assertFalse($converter->supports('csv', 'pdf'));
    }

    public function test_converter_rejects_missing_source_before_starting_process(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LibreOfficeDocumentConverter::class)->convert(
            storage_path('does-not-exist.docx'),
            'pdf'
        );
    }

    public function test_converter_creates_pdf_from_real_xlsx_when_libreoffice_is_installed(): void
    {
        $binary = (string) config('export_templates.converter.binary');
        if ($binary === '' || ! is_file($binary)) {
            $this->markTestSkipped('LibreOffice chưa được cài trong môi trường test.');
        }

        $source = sys_get_temp_dir().'\\codex-template-'.uniqid('', true).'.xlsx';
        $book = new Spreadsheet();
        $book->getActiveSheet()->setCellValue('A1', 'CDHC2 PDF smoke test');
        (new Xlsx($book))->save($source);
        $book->disconnectWorksheets();

        try {
            $pdf = app(LibreOfficeDocumentConverter::class)->convert($source, 'pdf');
            $this->assertFileExists($pdf);
            $this->assertGreaterThan(100, filesize($pdf));
            @unlink($pdf);
        } finally {
            @unlink($source);
        }
    }
}
