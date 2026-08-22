<?php

namespace App\Support;

use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất Word chuẩn văn bản hành chính + header/footer chỉnh sửa được.
 */
class WordExportTemplate
{
    public static function defaultHeaderLeft(): string
    {
        $parent = (string) SystemSettings::get(
            'shared',
            'parent_organization_name',
            'TỔNG CỤC HẬU CẦN - KỸ THUẬT'
        );
        $organization = (string) SystemSettings::get(
            'shared',
            'organization_name',
            'TRƯỜNG CAO ĐẲNG HẬU CẦN 2'
        );

        return $parent."\n".$organization."\n———————————————";
    }

    public static function defaultHeaderRight(): string
    {
        $now = Carbon::now();
        $heading = (string) SystemSettings::get(
            'shared',
            'national_heading',
            'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM'
        );
        $motto = (string) SystemSettings::get(
            'shared',
            'national_motto',
            'Độc lập - Tự do - Hạnh phúc'
        );
        $location = (string) SystemSettings::get(
            'shared',
            'document_location',
            'Thành phố Hồ Chí Minh'
        );

        return $heading."\n".$motto."\n———————————————\n".$location.', ngày … tháng … năm '.$now->format('Y');
    }

    public static function defaultFooterLeft(): string
    {
        return '';
    }

    public static function defaultFooterRight(): string
    {
        return '';
    }

    /**
     * @param  array{header_left?:string,header_right?:string,title?:string,footer_left?:string,footer_right?:string}  $meta
     * @param  callable(\PhpOffice\PhpWord\Element\Section):void  $bodyBuilder
     */
    public static function download(string $filename, array $meta, callable $bodyBuilder, bool $landscape = false): StreamedResponse
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'orientation' => $landscape ? 'landscape' : 'portrait',
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $headerLeft = (string) ($meta['header_left'] ?? self::defaultHeaderLeft());
        $headerRight = (string) ($meta['header_right'] ?? self::defaultHeaderRight());
        $title = (string) ($meta['title'] ?? '');
        $footerLeft = (string) ($meta['footer_left'] ?? '');
        $footerRight = (string) ($meta['footer_right'] ?? '');

        // Header 2 cột
        $headerTable = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);
        $headerTable->addRow();
        $left = $headerTable->addCell(5000);
        foreach (preg_split("/\r\n|\n|\r/", $headerLeft) ?: [] as $line) {
            $left->addText($line, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        $right = $headerTable->addCell(5000);
        foreach (preg_split("/\r\n|\n|\r/", $headerRight) ?: [] as $line) {
            $right->addText($line, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        if ($title !== '') {
            $section->addTextBreak(1);
            $section->addText($title, ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
            $section->addTextBreak(1);
        }

        // Body
        $bodyBuilder($section);

        // Footer signs
        if ($footerLeft !== '' || $footerRight !== '') {
            $section->addTextBreak(2);
            $sign = $section->addTable([
                'borderSize' => 0,
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
            ]);
            $sign->addRow();
            $fl = $sign->addCell(5000);
            foreach (preg_split("/\r\n|\n|\r/", $footerLeft) ?: [] as $line) {
                $fl->addText($line, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            }
            $fr = $sign->addCell(5000);
            foreach (preg_split("/\r\n|\n|\r/", $footerRight) ?: [] as $line) {
                $fr->addText($line, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            }
        }

        if (! str_ends_with(strtolower($filename), '.docx')) {
            $filename .= '.docx';
        }

        return response()->streamDownload(function () use ($phpWord) {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Helper: thêm bảng đơn giản vào section.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public static function addSimpleTable($section, array $headers, array $rows, array $widths = []): void
    {
        $colCount = max(1, count($headers));
        $defaultW = (int) floor(10000 / $colCount);
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);

        $table->addRow();
        foreach ($headers as $i => $h) {
            $w = $widths[$i] ?? $defaultW;
            $cell = $table->addCell($w, ['bgColor' => '1F4E79', 'valign' => 'center']);
            $cell->addText((string) $h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        foreach ($rows as $row) {
            $table->addRow();
            for ($i = 0; $i < $colCount; $i++) {
                $w = $widths[$i] ?? $defaultW;
                $cell = $table->addCell($w, ['valign' => 'center']);
                $cell->addText((string) ($row[$i] ?? ''), ['size' => 10], ['alignment' => Jc::LEFT]);
            }
        }
    }
}
