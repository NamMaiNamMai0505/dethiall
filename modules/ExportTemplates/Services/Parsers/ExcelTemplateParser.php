<?php

namespace Modules\ExportTemplates\Services\Parsers;

use Modules\ExportTemplates\Contracts\TemplateParserInterface;
use Modules\ExportTemplates\Exceptions\InvalidTemplateException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Style;

class ExcelTemplateParser implements TemplateParserInterface
{
    private const MAX_CELL_ELEMENTS = 15000;

    public function supports(string $fileExtension): bool
    {
        return in_array(strtolower($fileExtension), ['xlsx', 'xls', 'xlsm'], true);
    }

    public function parse(string $absolutePath): array
    {
        try {
            $reader = IOFactory::createReaderForFile($absolutePath);
            $reader->setReadDataOnly(false);
            $book = $reader->load($absolutePath);
        } catch (\Throwable $exception) {
            throw new InvalidTemplateException(
                'File Excel bị hỏng hoặc không đúng định dạng.',
                [$exception->getMessage()],
                $exception
            );
        }

        $targets = [];
        $elements = [];
        $placeholders = [];
        $cellMap = [];
        $warnings = [];
        $repeatRegions = [];
        $sheets = [];
        $cellElementCount = 0;

        try {
            foreach ($book->getWorksheetIterator() as $sheetIndex => $sheet) {
                $title = $sheet->getTitle();
                $encodedTitle = rawurlencode($title);
                $mergedRanges = array_values($sheet->getMergeCells());
                $tables = [];

                foreach ($sheet->getTableCollection() as $table) {
                    $range = $this->normalizeRange($table->getRange());
                    $ref = "excel:table:{$encodedTitle}:".$table->getName();
                    $target = [
                        'ref' => $ref,
                        'kind' => 'table',
                        'name' => $table->getName(),
                        'sheet' => $title,
                        'range' => $range,
                        'binding_type' => 'table',
                        'show_header' => $table->getShowHeaderRow(),
                        'show_totals' => $table->getShowTotalsRow(),
                    ];
                    $targets[] = $target;
                    $elements[] = $target;
                    $tables[] = $target;
                    $repeatRegions[] = [
                        'ref' => $ref,
                        'sheet' => $title,
                        'range' => $range,
                    ];
                }

                foreach ($mergedRanges as $range) {
                    $mergedTarget = [
                        'ref' => "excel:merge:{$encodedTitle}:{$range}",
                        'kind' => 'merged_range',
                        'sheet' => $title,
                        'range' => $range,
                        'binding_type' => 'scalar',
                    ];
                    $elements[] = $mergedTarget;
                    $targets[] = $mergedTarget;
                }

                foreach ($sheet->getCoordinates(false) as $coordinate) {
                    $cell = $sheet->getCell($coordinate);
                    $value = $this->cellValue($cell->getValue());
                    if ($value === '' && ! $cell->hasDataValidation()) {
                        continue;
                    }

                    if ($cellElementCount < self::MAX_CELL_ELEMENTS) {
                        $cellElement = [
                            'ref' => "excel:cell:{$encodedTitle}!{$coordinate}",
                            'kind' => 'cell',
                            'sheet' => $title,
                            'address' => $coordinate,
                            'value' => $value,
                            'data_type' => $cell->getDataType(),
                            'merged_range' => $this->mergedRangeFor($coordinate, $mergedRanges),
                            'style' => $this->style($sheet->getStyle($coordinate)),
                        ];
                        $elements[] = $cellElement;
                        $targets[] = [
                            'ref' => $cellElement['ref'],
                            'kind' => 'cell',
                            'sheet' => $title,
                            'address' => $coordinate,
                            'binding_type' => 'scalar',
                            'merged_range' => $cellElement['merged_range'],
                        ];
                        $cellElementCount++;
                    }

                    if (preg_match_all(
                        '/\{\{\s*([a-zA-Z0-9_.\[\]-]+)\s*\}\}/u',
                        $value,
                        $matches
                    )) {
                        foreach ($matches[1] as $occurrence => $dataKey) {
                            $target = [
                                'ref' => "excel:placeholder:{$encodedTitle}!{$coordinate}:{$occurrence}",
                                'kind' => 'placeholder',
                                'sheet' => $title,
                                'address' => $coordinate,
                                'data_key' => $dataKey,
                                'binding_type' => 'scalar',
                                'style' => $this->style($sheet->getStyle($coordinate)),
                            ];
                            $targets[] = $target;
                            $placeholders[] = $dataKey;
                            $cellMap["{$title}!{$coordinate}"] = $dataKey;
                        }
                    }
                }

                $rows = [];
                foreach ($sheet->getRowDimensions() as $row => $dimension) {
                    $rows[] = [
                        'row' => (int) $row,
                        'height' => $dimension->getRowHeight(),
                        'hidden' => $dimension->getVisible() === false,
                        'outline_level' => $dimension->getOutlineLevel(),
                    ];
                }

                $columns = [];
                foreach ($sheet->getColumnDimensions() as $column => $dimension) {
                    $columns[] = [
                        'column' => $column,
                        'width' => $dimension->getWidth(),
                        'auto_size' => $dimension->getAutoSize(),
                        'hidden' => $dimension->getVisible() === false,
                        'outline_level' => $dimension->getOutlineLevel(),
                    ];
                }

                $sheets[] = [
                    'index' => $sheetIndex,
                    'name' => $title,
                    'dimension' => $sheet->calculateWorksheetDimension(),
                    'highest_data_row' => $sheet->getHighestDataRow(),
                    'highest_data_column' => $sheet->getHighestDataColumn(),
                    'merged_ranges' => $mergedRanges,
                    'tables' => $tables,
                    'row_dimensions' => $rows,
                    'column_dimensions' => $columns,
                    'page_setup' => [
                        'orientation' => $sheet->getPageSetup()->getOrientation(),
                        'paper_size' => $sheet->getPageSetup()->getPaperSize(),
                        'fit_to_width' => $sheet->getPageSetup()->getFitToWidth(),
                        'fit_to_height' => $sheet->getPageSetup()->getFitToHeight(),
                        'print_area' => $sheet->getPageSetup()->getPrintArea(),
                    ],
                ];
            }

            foreach ($book->getDefinedNames() as $definedName) {
                if (! $definedName instanceof NamedRange) {
                    $warnings[] = "Bỏ qua named formula [{$definedName->getName()}].";

                    continue;
                }

                $sheet = $definedName->getWorksheet();
                $sheetName = $sheet?->getTitle() ?? '';
                $range = $this->normalizeRange($definedName->getRange());
                $scope = $definedName->getLocalOnly()
                    ? ($definedName->getScope()?->getTitle() ?? $sheetName)
                    : 'workbook';
                $ref = 'excel:named:'.rawurlencode($scope).':'.$definedName->getName();
                $isRepeat = preg_match('/(^repeat_|_repeat$)/i', $definedName->getName()) === 1;
                $target = [
                    'ref' => $ref,
                    'kind' => 'named_range',
                    'name' => $definedName->getName(),
                    'scope' => $scope,
                    'sheet' => $sheetName,
                    'range' => $range,
                    'binding_type' => $isRepeat || str_contains($range, ':')
                        ? 'table'
                        : 'scalar',
                    'repeat' => $isRepeat,
                ];
                $targets[] = $target;
                $elements[] = $target;

                if ($isRepeat) {
                    $repeatRegions[] = [
                        'ref' => $ref,
                        'sheet' => $sheetName,
                        'range' => $range,
                    ];
                }
            }
        } finally {
            $book->disconnectWorksheets();
            unset($book);
        }

        if ($cellElementCount >= self::MAX_CELL_ELEMENTS) {
            $warnings[] = 'Danh sách cell được giới hạn ở '.self::MAX_CELL_ELEMENTS.' phần tử.';
        }

        $errors = $this->overlappingRepeatRegionErrors($repeatRegions);

        return [
            'parser' => 'excel-v1',
            'document' => [
                'format' => 'excel',
                'sheet_count' => count($sheets),
                'sheets' => $sheets,
            ],
            'targets' => $targets,
            'elements' => $elements,
            'placeholders' => array_values(array_unique($placeholders)),
            'cell_map' => $cellMap,
            'hints' => [],
            'repeat_regions' => $repeatRegions,
            'validation' => [
                'valid' => $errors === [],
                'errors' => $errors,
                'warnings' => $warnings,
            ],
            'summary' => [
                'sheet_count' => count($sheets),
                'table_count' => count(array_filter(
                    $targets,
                    fn (array $target): bool => $target['kind'] === 'table'
                )),
                'named_range_count' => count(array_filter(
                    $targets,
                    fn (array $target): bool => $target['kind'] === 'named_range'
                )),
                'merged_range_count' => array_sum(array_map(
                    fn (array $sheet): int => count($sheet['merged_ranges']),
                    $sheets
                )),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function style(Style $style): array
    {
        $font = $style->getFont();
        $alignment = $style->getAlignment();
        $fill = $style->getFill();
        $borders = $style->getBorders();

        return [
            'font' => [
                'name' => $font->getName(),
                'size' => $font->getSize(),
                'bold' => $font->getBold(),
                'italic' => $font->getItalic(),
                'underline' => $font->getUnderline(),
                'color' => $this->color($font->getColor()),
            ],
            'alignment' => [
                'horizontal' => $alignment->getHorizontal(),
                'vertical' => $alignment->getVertical(),
                'wrap_text' => $alignment->getWrapText(),
                'text_rotation' => $alignment->getTextRotation(),
                'shrink_to_fit' => $alignment->getShrinkToFit(),
                'indent' => $alignment->getIndent(),
            ],
            'fill' => [
                'type' => $fill->getFillType(),
                'start_color' => $this->color($fill->getStartColor()),
                'end_color' => $this->color($fill->getEndColor()),
            ],
            'borders' => [
                'top' => $this->border($borders->getTop()),
                'right' => $this->border($borders->getRight()),
                'bottom' => $this->border($borders->getBottom()),
                'left' => $this->border($borders->getLeft()),
            ],
            'number_format' => $style->getNumberFormat()->getFormatCode(),
        ];
    }

    /**
     * @return array{style:string|null,color:?string}
     */
    private function border(Border $border): array
    {
        return [
            'style' => $border->getBorderStyle(),
            'color' => $this->color($border->getColor()),
        ];
    }

    private function color(Color $color): ?string
    {
        $argb = $color->getARGB();

        return $argb !== null && $argb !== '' ? $argb : null;
    }

    private function cellValue(mixed $value): string
    {
        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        return trim(json_encode($value, JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * @param  list<string>  $mergedRanges
     */
    private function mergedRangeFor(string $coordinate, array $mergedRanges): ?string
    {
        [$column, $row] = Coordinate::coordinateFromString($coordinate);
        $columnIndex = Coordinate::columnIndexFromString($column);

        foreach ($mergedRanges as $range) {
            $bounds = $this->rangeBounds($range);
            if (
                $bounds
                && $columnIndex >= $bounds[0]
                && $columnIndex <= $bounds[2]
                && $row >= $bounds[1]
                && $row <= $bounds[3]
            ) {
                return $range;
            }
        }

        return null;
    }

    private function normalizeRange(string $range): string
    {
        $range = trim($range);
        if (str_contains($range, '!')) {
            $range = (string) substr($range, strrpos($range, '!') + 1);
        }

        return str_replace('$', '', trim($range, "' "));
    }

    /**
     * @param  list<array{ref:string,sheet:string,range:string}>  $regions
     * @return list<string>
     */
    private function overlappingRepeatRegionErrors(array $regions): array
    {
        $errors = [];
        foreach ($regions as $leftIndex => $left) {
            $leftBounds = $this->rangeBounds($left['range']);
            if (! $leftBounds) {
                $errors[] = "Vùng lặp [{$left['ref']}] có range không hợp lệ.";

                continue;
            }

            for ($rightIndex = $leftIndex + 1; $rightIndex < count($regions); $rightIndex++) {
                $right = $regions[$rightIndex];
                if ($left['sheet'] !== $right['sheet']) {
                    continue;
                }
                $rightBounds = $this->rangeBounds($right['range']);
                if (! $rightBounds) {
                    continue;
                }

                $overlap = $leftBounds[0] <= $rightBounds[2]
                    && $leftBounds[2] >= $rightBounds[0]
                    && $leftBounds[1] <= $rightBounds[3]
                    && $leftBounds[3] >= $rightBounds[1];
                if ($overlap) {
                    $errors[] = "Vùng lặp [{$left['ref']}] chồng lấn [{$right['ref']}].";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array{int,int,int,int}|null
     */
    private function rangeBounds(string $range): ?array
    {
        $range = $this->normalizeRange($range);
        if (str_contains($range, ',')) {
            return null;
        }

        $parts = explode(':', $range, 2);
        $end = $parts[1] ?? $parts[0];

        try {
            [$startColumn, $startRow] = Coordinate::coordinateFromString($parts[0]);
            [$endColumn, $endRow] = Coordinate::coordinateFromString($end);
        } catch (\Throwable) {
            return null;
        }

        return [
            Coordinate::columnIndexFromString($startColumn),
            (int) $startRow,
            Coordinate::columnIndexFromString($endColumn),
            (int) $endRow,
        ];
    }
}
