<?php

namespace Modules\ScientificResearch\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ScientificResearchReportExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    public function __construct(private readonly Collection $registrations) {}

    public function array(): array
    {
        $rows = [
            ['BÁO CÁO NGHIÊN CỨU KHOA HỌC'],
            ['Ngày xuất', now()->format('d/m/Y H:i')],
            [],
            ['STT', 'Mã đề tài', 'Tên đề tài', 'Người đăng ký', 'Danh mục', 'Trạng thái', 'Tiến độ', 'Kinh phí', 'Số kết quả'],
        ];

        foreach ($this->registrations as $index => $row) {
            $rows[] = [
                $index + 1,
                $row->project_code,
                $row->title,
                $row->user?->name,
                trim(($row->researchCategory?->code ?: '').' '.$row->researchCategory?->name),
                $row->status,
                (int) $row->progress_percent.'%',
                (float) $row->budget,
                (int) ($row->results_count ?? 0),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Bao cao NCKH';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 18,
            'C' => 42,
            'D' => 24,
            'E' => 28,
            'F' => 18,
            'G' => 12,
            'H' => 16,
            'I' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(4, $this->registrations->count() + 4);
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A4:I4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                ]);
                $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G5:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
