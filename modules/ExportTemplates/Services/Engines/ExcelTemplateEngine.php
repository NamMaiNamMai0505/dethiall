<?php

namespace Modules\ExportTemplates\Services\Engines;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\ExportTemplates\Contracts\TemplateEngineInterface;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Models\ExportTemplateBinding;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\TemplateValueFormatter;
use Modules\ExportTemplates\Services\TemplateValueResolver;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelTemplateEngine implements TemplateEngineInterface
{
    public function __construct(
        private readonly TemplateValueResolver $resolver,
        private readonly TemplateValueFormatter $formatter
    ) {}

    public function supports(OutputFormat $format): bool
    {
        return $format === OutputFormat::EXCEL;
    }

    public function render(
        ExportTemplateVersion $version,
        array $data,
        ?array $bindings = null
    ): string {
        $source = $version->absolutePath();
        if (! $source || ! is_file($source)) {
            if ($version->builderDocument?->schema) {
                return $this->renderBuilder($version->builderDocument->schema, $data);
            }
            throw new \RuntimeException('Không tìm thấy file Excel template Active.');
        }

        $spreadsheet = IOFactory::load($source);
        $targets = collect($version->manifest['targets'] ?? [])->keyBy('ref');
        $bindingRows = $this->bindings($version, $bindings);
        $handled = [];

        try {
            foreach ($bindingRows as $binding) {
                $target = $targets->get($binding->target_ref);
                if (! $target || ! $this->isCollectionBinding($binding)) {
                    continue;
                }
                $this->applyCollection(
                    $spreadsheet,
                    $target,
                    $binding,
                    $bindingRows,
                    $targets,
                    $data,
                    $handled
                );
            }

            foreach ($bindingRows as $binding) {
                if (isset($handled[$binding->target_ref])) {
                    continue;
                }
                $target = $targets->get($binding->target_ref);
                if (! $target) {
                    continue;
                }
                $this->applyScalar($spreadsheet, $target, $binding, $data);
            }

            $destination = $this->temporaryPath('xlsx');
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($destination);

            return $destination;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /** Render Builder JSON trực tiếp thành workbook khi version không có file nguồn. */
    private function renderBuilder(array $schema, array $data): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $row = 1;
        foreach (($schema['blocks'] ?? []) as $block) {
            $type = (string) ($block['type'] ?? 'text');
            $props = is_array($block['props'] ?? null) ? $block['props'] : [];

            if ($type === 'header_pair') {
                $sheet->mergeCells("A{$row}:C".($row + 1));
                $sheet->mergeCells("D{$row}:F".($row + 1));
                $sheet->setCellValue(
                    "A{$row}",
                    $this->bindBuilderValue((string) ($props['left_text'] ?? ''), $data)
                );
                $sheet->setCellValue(
                    "D{$row}",
                    $this->bindBuilderValue((string) ($props['right_text'] ?? ''), $data)
                );

                foreach ([
                    ["A{$row}:C".($row + 1), is_array($props['left_style'] ?? null) ? $props['left_style'] : []],
                    ["D{$row}:F".($row + 1), is_array($props['right_style'] ?? null) ? $props['right_style'] : []],
                ] as [$range, $sideStyle]) {
                    $this->applyBuilderStyle(
                        $sheet->getStyle($range),
                        array_replace($props, $sideStyle),
                        true
                    );
                    $sheet->getStyle($range)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                }
                $sheet->getRowDimension($row)->setRowHeight(30);
                $sheet->getRowDimension($row + 1)->setRowHeight(8);
                $row += 3;
                continue;
            }

            if ($type === 'table') {
                $rows = max(1, (int) ($props['rows'] ?? 2));
                $cols = max(1, (int) ($props['columns'] ?? 4));
                $collectionKey = (string) ($props['collection_key'] ?? '');
                $items = $collectionKey !== '' ? $this->resolver->collection($data, $collectionKey) : [null];
                $tableStartRow = $row;
                foreach ($items as $item) {
                    for ($r = 0; $r < $rows; $r++) {
                        for ($c = 0; $c < $cols; $c++) {
                            $columnKey = $props['column_bindings'][$c] ?? null;
                            $cellText = $props['cell_text'][$r][$c] ?? null;
                            $value = is_string($cellText) && $cellText !== ''
                                ? $cellText
                                : ($r === 0
                                    ? (($props['header_'.$c] ?? $columnKey ?? ''))
                                    : ($columnKey ?: ($props['cell_'.$r.'_'.$c] ?? '')));
                            if ($r > 0 && $columnKey && (! is_string($cellText) || $cellText === '')) {
                                $value = $this->formatter->format($this->resolver->resolve($data, (string) $columnKey, is_array($item) ? $item : null));
                            }
                            $sheet->setCellValueByColumnAndRow($c + 1, $row + $r, $this->bindBuilderValue($value, $data, is_array($item) ? $item : null));
                            $cellStyle = is_array($props['cell_styles'][$r][$c] ?? null)
                                ? $props['cell_styles'][$r][$c]
                                : [];
                            $this->applyBuilderStyle(
                                $sheet->getStyleByColumnAndRow($c + 1, $row + $r),
                                array_replace($props, $cellStyle),
                                $r === 0
                            );
                        }
                        $sheet->getRowDimension($row + $r)->setRowHeight((float) ($props['row_height'] ?? 26));
                    }
                    $row += $rows;
                }
                $lastColumn = Coordinate::stringFromColumnIndex($cols);
                $tableEndRow = max($tableStartRow, $row - 1);
                $tableRange = "A{$tableStartRow}:{$lastColumn}{$tableEndRow}";
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(
                        ltrim((string) ($props['border_color'] ?? '475569'), '#')
                    ));
                $sheet->getStyle($tableRange)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle("A{$tableStartRow}:{$lastColumn}{$tableStartRow}")
                    ->getFont()->setBold(true);
                for ($column = 1; $column <= $cols; $column++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))
                        ->setWidth(max(8, (float) ($props['column_width'] ?? 120) / 7));
                }
                if (! empty($props['merge_range'])) {
                    try { $sheet->mergeCells($props['merge_range']); } catch (\Throwable) { /* ignore invalid draft range */ }
                }
                continue;
            }

            if ($type === 'page_break') {
                $sheet->setBreak("A{$row}", Worksheet::BREAK_ROW);
                continue;
            }
            if ($type === 'spacer') {
                $sheet->getRowDimension($row)->setRowHeight((float) ($props['height'] ?? 24));
                $row++;
                continue;
            }
            if ($type === 'divider') {
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_THIN);
                $row++;
                continue;
            }

            $text = $props['text'] ?? $props['label'] ?? '';
            $sheet->setCellValue('A'.$row, $this->bindBuilderValue((string) $text, $data));
            $sheet->mergeCells("A{$row}:F{$row}");
            $style = $sheet->getStyle("A{$row}:F{$row}");
            $font = $style->getFont();
            $font->setBold($type === 'heading' || (bool) ($props['bold'] ?? false));
            $font->setItalic((bool) ($props['italic'] ?? false));
            $font->setUnderline(
                ! empty($props['underline'])
                    ? \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE
                    : \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE
            );
            $font->setSize((float) ($props['font_size'] ?? ($type === 'heading' ? 14 : 11)));
            if (! empty($props['color'])) {
                $font->getColor()->setARGB(ltrim((string) $props['color'], '#'));
            }
            if (! empty($props['background'])) {
                $style->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB(ltrim((string) $props['background'], '#'));
            }
            $alignment = match ((string) ($props['align'] ?? 'left')) {
                'center' => Alignment::HORIZONTAL_CENTER,
                'right' => Alignment::HORIZONTAL_RIGHT,
                'justify' => Alignment::HORIZONTAL_JUSTIFY,
                default => Alignment::HORIZONTAL_LEFT,
            };
            $style->getAlignment()
                ->setHorizontal($alignment)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(
                max(18, (float) ($props['line_height'] ?? 1.4) * (float) ($props['font_size'] ?? 11))
            );
            $row++;
        }
        $destination = $this->temporaryPath('xlsx');
        IOFactory::createWriter($book, 'Xlsx')->save($destination);
        $book->disconnectWorksheets();
        return $destination;
    }

    private function bindBuilderValue(string $value, array $data, ?array $item = null): string
    {
        return (string) preg_replace_callback('/\{\{\s*([^}]+?)\s*\}\}/', function (array $match) use ($data, $item): string {
            return $this->formatter->format($this->resolver->resolve($data, trim($match[1]), $item));
        }, $value);
    }

    private function applyBuilderStyle(Style $style, array $props, bool $isHeader = false): void
    {
        $font = $style->getFont();
        $font->setBold($isHeader || (bool) ($props['bold'] ?? false));
        $font->setItalic((bool) ($props['italic'] ?? false));
        $font->setUnderline(
            ! empty($props['underline'])
                ? \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE
                : \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE
        );
        $font->setSize((float) ($props['font_size'] ?? 11));
        if (! empty($props['color'])) {
            $font->getColor()->setARGB(ltrim((string) $props['color'], '#'));
        }
        if (! empty($props['background'])) {
            $style->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB(ltrim((string) $props['background'], '#'));
        }
        $horizontal = match ((string) ($props['align'] ?? ($isHeader ? 'center' : 'left'))) {
            'center' => Alignment::HORIZONTAL_CENTER,
            'right' => Alignment::HORIZONTAL_RIGHT,
            'justify' => Alignment::HORIZONTAL_JUSTIFY,
            default => Alignment::HORIZONTAL_LEFT,
        };
        $style->getAlignment()
            ->setHorizontal($horizontal)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }

    /**
     * @return Collection<int, ExportTemplateBinding>
     */
    private function bindings(
        ExportTemplateVersion $version,
        ?array $bindings
    ): Collection {
        if ($bindings === null) {
            return $version->bindings()->get();
        }

        return collect($bindings)->map(
            static fn (array $binding): ExportTemplateBinding => new ExportTemplateBinding($binding)
        );
    }

    private function isCollectionBinding(ExportTemplateBinding $binding): bool
    {
        return in_array(
            $binding->binding_type?->value ?? (string) $binding->binding_type,
            ['table', 'grouped_table', 'repeating_row'],
            true
        );
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $targets
     * @param  array<string, bool>  $handled
     */
    private function applyCollection(
        Spreadsheet $book,
        array $target,
        ExportTemplateBinding $binding,
        Collection $bindings,
        Collection $targets,
        array $data,
        array &$handled
    ): void {
        $sheet = $this->sheet($book, $target);
        $range = (string) ($target['range'] ?? '');
        $items = $this->resolver->collection($data, $binding->data_key);
        if (! $sheet || $range === '' || $items === []) {
            return;
        }

        [$startColumn, $startRow, $endColumn, $endRow] = $this->rangeBounds($range);
        $hasHeader = ($target['kind'] ?? null) === 'table'
            ? (bool) ($target['show_header'] ?? true)
            : false;
        $dataStartRow = $startRow + ($hasHeader ? 1 : 0);
        $prototypeRow = min($dataStartRow, $endRow);
        $capacity = max(1, $endRow - $dataStartRow + 1);
        $required = count($items);

        if ($required > $capacity) {
            $extra = $required - $capacity;
            $sheet->insertNewRowBefore($endRow + 1, $extra);
            for ($row = $endRow + 1; $row <= $endRow + $extra; $row++) {
                $sheet->duplicateStyle(
                    $sheet->getStyle(
                        Coordinate::stringFromColumnIndex($startColumn).$prototypeRow.':'.
                        Coordinate::stringFromColumnIndex($endColumn).$prototypeRow
                    ),
                    Coordinate::stringFromColumnIndex($startColumn).$row.':'.
                    Coordinate::stringFromColumnIndex($endColumn).$row
                );
                $sheet->getRowDimension($row)->setRowHeight(
                    $sheet->getRowDimension($prototypeRow)->getRowHeight()
                );
            }
            $endRow += $extra;
            if (($target['kind'] ?? null) === 'table' && ! empty($target['name'])) {
                $sheet->getTableByName($target['name'])?->setRange(
                    Coordinate::stringFromColumnIndex($startColumn).$startRow.':'.
                    Coordinate::stringFromColumnIndex($endColumn).$endRow
                );
            }
        }

        $columnBindings = $this->collectionColumnBindings(
            $binding,
            $bindings,
            $targets,
            $sheet->getTitle(),
            $startColumn,
            $endColumn,
            $dataStartRow,
            $endRow
        );

        foreach ($items as $offset => $item) {
            $row = $dataStartRow + $offset;
            if ($columnBindings === []) {
                $values = array_values($item);
                for ($column = $startColumn; $column <= $endColumn; $column++) {
                    $this->setCellValue(
                        $sheet,
                        Coordinate::stringFromColumnIndex($column).$row,
                        $values[$column - $startColumn] ?? ''
                    );
                }

                continue;
            }

            foreach ($columnBindings as $column => $child) {
                $coordinate = Coordinate::stringFromColumnIndex($column).$row;
                $value = $this->resolver->resolve($data, $child->data_key, $item);
                $this->setCellValue(
                    $sheet,
                    $coordinate,
                    $this->formatter->format($value, $child->formatter)
                );
                $this->applyPresentation($sheet, $coordinate, $child);
                $handled[$child->target_ref] = true;
            }
        }

        for ($row = $dataStartRow + $required; $row <= $endRow; $row++) {
            for ($column = $startColumn; $column <= $endColumn; $column++) {
                $this->setCellValue(
                    $sheet,
                    Coordinate::stringFromColumnIndex($column).$row,
                    ''
                );
            }
        }

        $renderedRange = Coordinate::stringFromColumnIndex($startColumn).$startRow.':'.
            Coordinate::stringFromColumnIndex($endColumn).$endRow;
        $this->applyPresentation($sheet, $renderedRange, $binding);
        $handled[$binding->target_ref] = true;
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $targets
     * @return array<int, ExportTemplateBinding>
     */
    private function collectionColumnBindings(
        ExportTemplateBinding $parent,
        Collection $bindings,
        Collection $targets,
        string $sheetName,
        int $startColumn,
        int $endColumn,
        int $startRow,
        int $endRow
    ): array {
        $prefix = $parent->data_key.'[].';
        $result = [];

        foreach ($bindings as $binding) {
            if (! str_starts_with($binding->data_key, $prefix)) {
                continue;
            }
            $target = $targets->get($binding->target_ref);
            if (! $target || ($target['sheet'] ?? null) !== $sheetName || empty($target['address'])) {
                continue;
            }
            [$columnName, $row] = Coordinate::coordinateFromString($target['address']);
            $column = Coordinate::columnIndexFromString($columnName);
            if (
                $column >= $startColumn
                && $column <= $endColumn
                && $row >= $startRow
                && $row <= $endRow
                && ! isset($result[$column])
            ) {
                $result[$column] = $binding;
            }
        }

        return $result;
    }

    private function applyScalar(
        Spreadsheet $book,
        array $target,
        ExportTemplateBinding $binding,
        array $data
    ): void {
        $sheet = $this->sheet($book, $target);
        if (! $sheet) {
            return;
        }

        $coordinate = $target['address'] ?? null;
        if (! $coordinate && ! empty($target['range'])) {
            $coordinate = explode(':', $target['range'], 2)[0];
        }
        if (! $coordinate) {
            return;
        }

        $value = $this->resolver->resolve($data, $binding->data_key);
        if (($binding->binding_type?->value ?? null) === 'image') {
            $this->insertImage($sheet, $coordinate, $value, $binding);
        } else {
            $this->setCellValue(
                $sheet,
                $coordinate,
                $this->formatter->format($value, $binding->formatter)
            );
        }
        $this->applyPresentation($sheet, $target['range'] ?? $coordinate, $binding);
    }

    private function sheet(Spreadsheet $book, array $target): ?Worksheet
    {
        $name = (string) ($target['sheet'] ?? '');

        return $name !== '' ? $book->getSheetByName($name) : $book->getActiveSheet();
    }

    private function insertImage(
        Worksheet $sheet,
        string $coordinate,
        mixed $value,
        ExportTemplateBinding $binding
    ): void {
        $path = (string) $value;
        if (! is_file($path)) {
            $this->setCellValue($sheet, $coordinate, $path);

            return;
        }

        $drawing = new Drawing;
        $drawing->setPath($path);
        $drawing->setCoordinates($coordinate);
        $drawing->setWorksheet($sheet);
        if (! empty($binding->style_overrides['width'])) {
            $drawing->setWidth((int) $binding->style_overrides['width']);
        }
        if (! empty($binding->style_overrides['height'])) {
            $drawing->setHeight((int) $binding->style_overrides['height']);
        }
    }

    private function applyPresentation(
        Worksheet $sheet,
        string $range,
        ExportTemplateBinding $binding
    ): void {
        $style = $binding->style_overrides ?? [];
        $options = $binding->options ?? [];
        $sheetStyle = $sheet->getStyle($range);

        if (! empty($style['font_name'])) {
            $sheetStyle->getFont()->setName($style['font_name']);
        }
        if (! empty($style['font_size'])) {
            $sheetStyle->getFont()->setSize((float) $style['font_size']);
        }
        if (array_key_exists('bold', $style)) {
            $sheetStyle->getFont()->setBold((bool) $style['bold']);
        }
        if (array_key_exists('italic', $style)) {
            $sheetStyle->getFont()->setItalic((bool) $style['italic']);
        }
        if (! empty($style['align'])) {
            $sheetStyle->getAlignment()->setHorizontal(match ($style['align']) {
                'center' => Alignment::HORIZONTAL_CENTER,
                'right' => Alignment::HORIZONTAL_RIGHT,
                'justify' => Alignment::HORIZONTAL_JUSTIFY,
                default => Alignment::HORIZONTAL_LEFT,
            });
        }
        if (! empty($style['vertical_align'])) {
            $sheetStyle->getAlignment()->setVertical(match ($style['vertical_align']) {
                'top' => Alignment::VERTICAL_TOP,
                'bottom' => Alignment::VERTICAL_BOTTOM,
                default => Alignment::VERTICAL_CENTER,
            });
        }
        if (! empty($style['border_style'])) {
            $borderStyle = match ($style['border_style']) {
                'none' => Border::BORDER_NONE,
                'medium' => Border::BORDER_MEDIUM,
                'thick' => Border::BORDER_THICK,
                'dashed' => Border::BORDER_DASHED,
                'dotted' => Border::BORDER_DOTTED,
                'double' => Border::BORDER_DOUBLE,
                default => Border::BORDER_THIN,
            };
            $color = ltrim((string) ($style['border_color'] ?? '#000000'), '#');
            $sheetStyle->getBorders()->getAllBorders()->setBorderStyle($borderStyle);
            $sheetStyle->getBorders()->getAllBorders()->getColor()->setRGB($color);
        }
        if (isset($style['padding'])) {
            $sheetStyle->getAlignment()->setIndent(
                max(0, min(250, (int) round((float) $style['padding'] / 4)))
            );
        }

        [$startColumn, $startRow] = Coordinate::coordinateFromString(explode(':', $range, 2)[0]);
        if (! empty($options['row_height']) || ! empty($style['height'])) {
            $sheet->getRowDimension($startRow)->setRowHeight(
                (float) ($options['row_height'] ?? $style['height'])
            );
        }
        if (! empty($options['column_width']) || ! empty($style['width'])) {
            $sheet->getColumnDimension($startColumn)->setWidth(
                (float) ($options['column_width'] ?? ((float) $style['width'] / 7))
            );
        }

        $action = $options['cell_action'] ?? 'none';
        if ($action === 'merge' && ! empty($options['merge_range'])) {
            $sheet->mergeCells($options['merge_range']);
        } elseif ($action === 'split') {
            foreach ($sheet->getMergeCells() as $mergedRange) {
                if (
                    $mergedRange === $range
                    || $this->coordinateInRange($startColumn.$startRow, $mergedRange)
                ) {
                    $sheet->unmergeCells($mergedRange);
                    break;
                }
            }
        }
    }

    /**
     * @return array{int,int,int,int}
     */
    private function rangeBounds(string $range): array
    {
        [$start, $end] = array_pad(explode(':', $range, 2), 2, null);
        $end ??= $start;
        [$startColumn, $startRow] = Coordinate::coordinateFromString($start);
        [$endColumn, $endRow] = Coordinate::coordinateFromString($end);

        return [
            Coordinate::columnIndexFromString($startColumn),
            (int) $startRow,
            Coordinate::columnIndexFromString($endColumn),
            (int) $endRow,
        ];
    }

    private function temporaryPath(string $extension): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'lms-template-'.Str::uuid().'.'.$extension;
    }

    private function coordinateInRange(string $coordinate, string $range): bool
    {
        [$column, $row] = Coordinate::coordinateFromString($coordinate);
        $columnIndex = Coordinate::columnIndexFromString($column);
        [$startColumn, $startRow, $endColumn, $endRow] = $this->rangeBounds($range);

        return $columnIndex >= $startColumn
            && $columnIndex <= $endColumn
            && $row >= $startRow
            && $row <= $endRow;
    }

    private function setCellValue(
        Worksheet $sheet,
        string $coordinate,
        mixed $value
    ): void {
        if (
            is_string($value)
            && preg_match('/^[=+\-@]/', ltrim($value)) === 1
        ) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);

            return;
        }

        $sheet->setCellValue($coordinate, $value);
    }
}
