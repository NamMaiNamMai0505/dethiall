<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Exceptions\InvalidTemplateException;
use Modules\ExportTemplates\Services\Parsers\TemplateStructureAnalyzer;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TemplateStructureParserTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_excel_parser_reads_cells_named_ranges_merge_table_dimensions_and_style(): void
    {
        $path = $this->excelTemplate();

        $manifest = $this->analyzer()->analyze($path, 'xlsx');

        $this->assertSame('excel-v1', $manifest['parser']);
        $this->assertTrue($manifest['validation']['valid']);
        $this->assertSame(1, $manifest['summary']['sheet_count']);
        $this->assertSame(1, $manifest['summary']['table_count']);
        $this->assertSame(1, $manifest['summary']['named_range_count']);
        $this->assertSame(1, $manifest['summary']['merged_range_count']);
        $this->assertContains('class.name', $manifest['placeholders']);

        $placeholder = collect($manifest['targets'])->firstWhere('kind', 'placeholder');
        $namedRange = collect($manifest['targets'])->firstWhere('kind', 'named_range');
        $table = collect($manifest['targets'])->firstWhere('kind', 'table');
        $cell = collect($manifest['elements'])->firstWhere('ref', 'excel:cell:LHL!A1');

        $this->assertSame('A1', $placeholder['address']);
        $this->assertSame('class_name', $namedRange['name']);
        $this->assertSame('A1', $namedRange['range']);
        $this->assertSame('A4:B6', $table['range']);
        $this->assertTrue($cell['style']['font']['bold']);
        $this->assertSame(14.0, $cell['style']['font']['size']);
        $this->assertSame(Alignment::HORIZONTAL_CENTER, $cell['style']['alignment']['horizontal']);

        $sheet = $manifest['document']['sheets'][0];
        $this->assertSame(30.0, collect($sheet['row_dimensions'])->firstWhere('row', 1)['height']);
        $this->assertSame(24.0, collect($sheet['column_dimensions'])->firstWhere('column', 'A')['width']);
    }

    public function test_excel_parser_rejects_overlapping_repeat_regions(): void
    {
        $path = $this->excelTemplate(true);

        try {
            $this->analyzer()->analyze($path, 'xlsx');
            $this->fail('Vùng lặp chồng lấn phải bị từ chối.');
        } catch (InvalidTemplateException $exception) {
            $this->assertStringContainsString('chồng lấn', implode(' ', $exception->errors()));
        }
    }

    public function test_word_parser_reads_split_runs_bookmark_content_control_table_image_header_footer(): void
    {
        $path = $this->wordTemplate();

        $manifest = $this->analyzer()->analyze($path, 'docx');

        $this->assertSame('word-v1', $manifest['parser']);
        $this->assertTrue($manifest['validation']['valid']);
        $this->assertTrue($manifest['document']['has_header']);
        $this->assertTrue($manifest['document']['has_footer']);
        $this->assertContains('class.name', $manifest['placeholders']);
        $this->assertContains('organization.name', $manifest['placeholders']);
        $this->assertSame('landscape', $manifest['document']['layout']['orientation']);
        $this->assertSame(1, $manifest['summary']['bookmark_count']);
        $this->assertSame(1, $manifest['summary']['content_control_count']);
        $this->assertSame(1, $manifest['summary']['table_count']);
        $this->assertSame(1, $manifest['summary']['image_count']);

        $bookmark = collect($manifest['targets'])->firstWhere('kind', 'bookmark');
        $contentControl = collect($manifest['targets'])->firstWhere('kind', 'content_control');
        $cell = collect($manifest['elements'])->firstWhere('kind', 'table_cell');

        $this->assertSame('class_name', $bookmark['name']);
        $this->assertSame('teacher.name', $contentControl['tag']);
        $this->assertSame('Y54', $cell['text']);
        $this->assertSame(2, $cell['style']['grid_span']);
    }

    public function test_word_parser_rejects_duplicate_bookmarks(): void
    {
        $path = $this->wordTemplate(true);

        try {
            $this->analyzer()->analyze($path, 'docx');
            $this->fail('Bookmark trùng phải bị từ chối.');
        } catch (InvalidTemplateException $exception) {
            $this->assertStringContainsString('Bookmark [class_name] bị trùng', implode(' ', $exception->errors()));
        }
    }

    public function test_guard_rejects_corrupted_ooxml(): void
    {
        $path = $this->temporaryPath('docx');
        file_put_contents($path, 'not-a-valid-docx');

        $this->expectException(InvalidTemplateException::class);

        $this->analyzer()->analyze($path, 'docx');
    }

    private function analyzer(): TemplateStructureAnalyzer
    {
        return app(TemplateStructureAnalyzer::class);
    }

    private function excelTemplate(bool $overlappingRepeat = false): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LHL');
        $sheet->setCellValue('A1', '{{class.name}}');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('A2', 'LỊCH HUẤN LUYỆN');
        $sheet->fromArray([
            ['Tiết', 'Môn'],
            ['1-3', 'TTT'],
            ['4-5', 'GPSL'],
        ], null, 'A4');
        $sheet->addTable(new Table('A4:B6', 'ScheduleRows'));
        $spreadsheet->addNamedRange(new NamedRange('class_name', $sheet, 'A1'));

        if ($overlappingRepeat) {
            $spreadsheet->addNamedRange(new NamedRange('repeat_schedule', $sheet, 'A5:B7'));
        }

        $path = $this->temporaryPath('xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function wordTemplate(bool $duplicateBookmark = false): string
    {
        $path = $this->temporaryPath('docx');
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        $duplicate = $duplicateBookmark
            ? '<w:p><w:bookmarkStart w:id="2" w:name="class_name"/><w:r><w:t>Duplicate</w:t></w:r><w:bookmarkEnd w:id="2"/></w:p>'
            : '';
        $document = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
 xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
 <w:body>
  <w:p><w:r><w:t>{{class</w:t></w:r><w:r><w:t>.name}}</w:t></w:r></w:p>
  <w:p><w:bookmarkStart w:id="1" w:name="class_name"/><w:r><w:t>Lớp Y54</w:t></w:r><w:bookmarkEnd w:id="1"/></w:p>
  <w:sdt>
   <w:sdtPr><w:tag w:val="teacher.name"/><w:alias w:val="Giảng viên"/><w:id w:val="42"/></w:sdtPr>
   <w:sdtContent><w:p><w:r><w:t>Nguyễn Văn A</w:t></w:r></w:p></w:sdtContent>
  </w:sdt>
  <w:tbl>
   <w:tr>
    <w:tc><w:tcPr><w:gridSpan w:val="2"/><w:tcW w:w="2400" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>Y54</w:t></w:r></w:p></w:tc>
   </w:tr>
  </w:tbl>
  <w:p><w:r><w:drawing><a:blip r:embed="rId5"/></w:drawing></w:r></w:p>
  %DUPLICATE%
  <w:sectPr><w:pgSz w:w="23811" w:h="16839" w:orient="landscape"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>
 </w:body>
</w:document>
XML;
        $document = str_replace('%DUPLICATE%', $duplicate, $document);

        $header = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>{{organization</w:t></w:r><w:r><w:t>.name}}</w:t></w:r></w:p></w:hdr>
XML;
        $footer = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Trang 1</w:t></w:r></w:p></w:ftr>
XML;

        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/header1.xml', $header);
        $zip->addFromString('word/footer1.xml', $footer);
        $zip->close();

        return $path;
    }

    private function temporaryPath(string $extension): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('template-parser-', true).'.'.$extension;
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
