<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Contracts\TemplateDataProviderInterface;
use Modules\ExportTemplates\Models\ExportTemplateBinding;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TemplatePreviewBuilder
{
    private const MAX_EXCEL_ROWS = 120;

    private const MAX_EXCEL_COLUMNS = 50;

    public function __construct(
        private readonly TemplateDataExplorer $explorer
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ExportTemplateVersion $version,
        TemplateDataProviderInterface $provider,
        ?string $selectedSheet = null
    ): array {
        $manifest = $version->manifest ?? [];
        $mockData = $provider->mockData();
        $schema = $provider->schema();
        $bindings = $version->bindings()->get()->keyBy('target_ref');
        $format = (string) ($manifest['document']['format'] ?? '');

        return $format === 'excel'
            ? $this->excel($manifest, $mockData, $schema, $bindings, $selectedSheet)
            : $this->word($manifest, $mockData, $schema, $bindings);
    }

    /**
     * @param  array<string, ExportTemplateBinding>  $bindings
     * @return array<string, mixed>
     */
    private function excel(
        array $manifest,
        array $mockData,
        array $schema,
        mixed $bindings,
        ?string $selectedSheet
    ): array {
        $sheetDefinitions = $manifest['document']['sheets'] ?? [];
        $sheetNames = array_values(array_column($sheetDefinitions, 'name'));
        $activeSheet = in_array($selectedSheet, $sheetNames, true)
            ? $selectedSheet
            : ($sheetNames[0] ?? '');
        $sheet = collect($sheetDefinitions)->firstWhere('name', $activeSheet) ?? [];
        [$maxColumn, $maxRow] = $this->dimension(
            (string) ($sheet['dimension'] ?? 'A1'),
            self::MAX_EXCEL_COLUMNS,
            self::MAX_EXCEL_ROWS
        );

        $elements = collect($manifest['elements'] ?? [])
            ->where('kind', 'cell')
            ->where('sheet', $activeSheet)
            ->keyBy('address');
        $targets = collect($manifest['targets'] ?? [])
            ->where('sheet', $activeSheet);
        $targetsByAddress = $targets
            ->filter(fn (array $target): bool => ! empty($target['address']))
            ->groupBy('address');
        $mergeState = $this->mergeState($sheet['merged_ranges'] ?? [], $maxColumn, $maxRow);
        $rows = [];

        for ($row = 1; $row <= $maxRow; $row++) {
            $cells = [];
            for ($column = 1; $column <= $maxColumn; $column++) {
                $address = Coordinate::stringFromColumnIndex($column).$row;
                if (isset($mergeState['covered'][$address])) {
                    continue;
                }

                $element = $elements->get($address, []);
                $target = $this->preferredTarget(
                    ($targetsByAddress->get($address) ?? collect())->all(),
                    $bindings
                );
                $merge = $mergeState['starts'][$address] ?? ['rowspan' => 1, 'colspan' => 1];
                $presentation = $this->presentation(
                    (string) ($element['value'] ?? ''),
                    $target,
                    $element['style'] ?? [],
                    $bindings,
                    $mockData,
                    $schema
                );
                $cells[] = [
                    'address' => $address,
                    'rowspan' => $merge['rowspan'],
                    'colspan' => $merge['colspan'],
                    'target_ref' => $target['ref'] ?? null,
                    'target_kind' => $target['kind'] ?? null,
                    ...$presentation,
                ];
            }
            $rows[] = [
                'number' => $row,
                'height' => $this->dimensionValue(
                    $sheet['row_dimensions'] ?? [],
                    'row',
                    $row,
                    'height'
                ),
                'cells' => $cells,
            ];
        }

        $columns = [];
        for ($index = 1; $index <= $maxColumn; $index++) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $columns[] = [
                'letter' => $letter,
                'width' => $this->dimensionValue(
                    $sheet['column_dimensions'] ?? [],
                    'column',
                    $letter,
                    'width'
                ),
            ];
        }

        $regions = $targets
            ->filter(fn (array $target): bool => in_array(
                $target['kind'] ?? '',
                ['table', 'named_range', 'merged_range'],
                true
            ))
            ->map(function (array $target) use ($bindings, $mockData, $schema): array {
                $presentation = $this->presentation(
                    (string) ($target['name'] ?? $target['range'] ?? ''),
                    $target,
                    [],
                    $bindings,
                    $mockData,
                    $schema
                );

                return [
                    'ref' => $target['ref'],
                    'kind' => $target['kind'],
                    'label' => $target['name'] ?? $target['range'] ?? $target['ref'],
                    ...$presentation,
                ];
            })
            ->values()
            ->all();

        return [
            'format' => 'excel',
            'sheets' => $sheetNames,
            'active_sheet' => $activeSheet,
            'columns' => $columns,
            'rows' => $rows,
            'regions' => $regions,
            'truncated' => $this->isTruncated((string) ($sheet['dimension'] ?? 'A1')),
            'page_setup' => $sheet['page_setup'] ?? [],
        ];
    }

    /**
     * @param  array<string, ExportTemplateBinding>  $bindings
     * @return array<string, mixed>
     */
    private function word(
        array $manifest,
        array $mockData,
        array $schema,
        mixed $bindings
    ): array {
        $targets = collect($manifest['targets'] ?? []);
        $parts = [];

        $partDefinitions = collect($manifest['document']['parts'] ?? [])
            ->sortBy(fn (array $part): int => match ($part['type'] ?? '') {
                'header' => 0,
                'document' => 1,
                'footer' => 2,
                default => 3,
            });

        foreach ($partDefinitions as $partDefinition) {
            $partName = (string) $partDefinition['name'];
            $elements = collect($manifest['elements'] ?? [])->where('part', $partName);
            $paragraphs = $elements
                ->where('kind', 'paragraph')
                ->map(function (array $element) use (
                    $targets,
                    $bindings,
                    $mockData,
                    $schema
                ): array {
                    $paragraphTargets = $targets
                        ->where('part', $element['part'])
                        ->where('paragraph_index', $element['index'])
                        ->values()
                        ->all();
                    $target = $this->preferredTarget($paragraphTargets, $bindings);

                    return [
                        'ref' => $element['ref'],
                        'target_ref' => $target['ref'] ?? null,
                        ...$this->presentation(
                            (string) ($element['text'] ?? ''),
                            $target,
                            $element['style'] ?? [],
                            $bindings,
                            $mockData,
                            $schema
                        ),
                    ];
                })
                ->values()
                ->all();
            $tables = $elements
                ->where('kind', 'table')
                ->map(function (array $table) use (
                    $bindings,
                    $mockData,
                    $schema
                ): array {
                    $rows = array_map(function (array $row) use (
                        $bindings,
                        $mockData,
                        $schema
                    ): array {
                        $cells = array_map(function (array $cell) use (
                            $bindings,
                            $mockData,
                            $schema
                        ): array {
                            return [
                                'ref' => $cell['ref'],
                                'target_ref' => $cell['ref'],
                                'colspan' => (int) ($cell['style']['grid_span'] ?? 1),
                                ...$this->presentation(
                                    (string) ($cell['text'] ?? ''),
                                    ['ref' => $cell['ref'], 'kind' => 'table_cell'],
                                    $cell['style'] ?? [],
                                    $bindings,
                                    $mockData,
                                    $schema
                                ),
                            ];
                        }, $row['cells'] ?? []);

                        return [
                            'height' => $row['height'] ?? null,
                            'cells' => $cells,
                        ];
                    }, $table['rows'] ?? []);

                    return [
                        'ref' => $table['ref'],
                        'target_ref' => $table['ref'],
                        'rows' => $rows,
                    ];
                })
                ->values()
                ->all();
            $controls = $targets
                ->where('part', $partName)
                ->filter(fn (array $target): bool => in_array(
                    $target['kind'] ?? '',
                    ['bookmark', 'content_control', 'image_control', 'image'],
                    true
                ))
                ->map(fn (array $target): array => [
                    'ref' => $target['ref'],
                    'kind' => $target['kind'],
                    'label' => $target['name']
                        ?? $target['alias']
                        ?? $target['tag']
                        ?? $target['text']
                        ?? $target['ref'],
                    ...$this->presentation(
                        (string) ($target['text'] ?? ''),
                        $target,
                        [],
                        $bindings,
                        $mockData,
                        $schema
                    ),
                ])
                ->values()
                ->all();

            $parts[] = [
                'name' => $partName,
                'type' => $partDefinition['type'],
                'paragraphs' => $paragraphs,
                'tables' => $tables,
                'controls' => $controls,
            ];
        }

        return [
            'format' => 'word',
            'layout' => $manifest['document']['layout'] ?? [],
            'parts' => $parts,
            'truncated' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentation(
        string $fallback,
        ?array $target,
        array $baseStyle,
        mixed $bindings,
        array $mockData,
        array $schema
    ): array {
        $binding = $target ? $bindings->get($target['ref'] ?? '') : null;
        $dataKey = $binding?->data_key ?: ($target['data_key'] ?? null);
        $value = $fallback;

        if ($dataKey && $this->explorer->find($schema, $dataKey)) {
            $resolved = $this->explorer->value($mockData, $dataKey);
            $value = $this->displayValue($resolved, $binding?->formatter);
        }

        $overrides = $binding?->style_overrides ?? [];

        return [
            'value' => $value,
            'data_key' => $dataKey,
            'bound' => $binding !== null,
            'style_overrides' => $overrides,
            'css' => $this->css($baseStyle, $overrides),
        ];
    }

    private function displayValue(mixed $value, ?string $formatter): string
    {
        if (is_array($value)) {
            return count($value).' dòng dữ liệu';
        }
        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }
        if ($value === null) {
            return '';
        }

        if ($formatter && strtotime((string) $value) !== false) {
            return date($formatter, strtotime((string) $value));
        }

        return (string) $value;
    }

    private function css(array $base, array $overrides): string
    {
        $font = $base['font'] ?? [];
        $alignment = $base['alignment'] ?? [];
        $declarations = [];
        $fontName = $overrides['font_name'] ?? $font['name'] ?? null;
        if ($fontName) {
            $declarations[] = 'font-family:'.preg_replace('/[^a-zA-Z0-9 _-]/', '', (string) $fontName);
        }
        $fontSize = $overrides['font_size']
            ?? $font['size']
            ?? (isset($font['size_half_points']) ? ((float) $font['size_half_points'] / 2) : null);
        if ($fontSize) {
            $declarations[] = 'font-size:'.(float) $fontSize.'pt';
        }
        $bold = $overrides['bold'] ?? $font['bold'] ?? false;
        $italic = $overrides['italic'] ?? $font['italic'] ?? false;
        $declarations[] = 'font-weight:'.($bold ? '700' : '400');
        $declarations[] = 'font-style:'.($italic ? 'italic' : 'normal');

        $align = $overrides['align']
            ?? $alignment['horizontal']
            ?? $base['alignment']
            ?? null;
        if (is_string($align) && in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            $declarations[] = 'text-align:'.$align;
        }
        if (! empty($overrides['vertical_align'])) {
            $declarations[] = 'vertical-align:'.$overrides['vertical_align'];
        }
        foreach (['width', 'height', 'padding', 'margin'] as $property) {
            if (isset($overrides[$property])) {
                $declarations[] = str_replace('_', '-', $property).':'.(float) $overrides[$property].'px';
            }
        }
        if (! empty($overrides['border_style']) && $overrides['border_style'] !== 'none') {
            $borderWidth = match ($overrides['border_style']) {
                'medium' => 2,
                'thick' => 3,
                default => 1,
            };
            $borderStyle = in_array(
                $overrides['border_style'],
                ['dashed', 'dotted', 'double'],
                true
            ) ? $overrides['border_style'] : 'solid';
            $color = $overrides['border_color'] ?? '#0F172A';
            $declarations[] = "border:{$borderWidth}px {$borderStyle} {$color}";
        }

        return implode(';', $declarations);
    }

    /**
     * @param  list<array<string, mixed>>  $targets
     */
    private function preferredTarget(array $targets, mixed $bindings): ?array
    {
        foreach ($targets as $target) {
            if ($bindings->has($target['ref'] ?? '')) {
                return $target;
            }
        }
        foreach ($targets as $target) {
            if (($target['kind'] ?? null) === 'placeholder') {
                return $target;
            }
        }

        return $targets[0] ?? null;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function dimension(string $dimension, int $columnLimit, int $rowLimit): array
    {
        $last = str_contains($dimension, ':')
            ? explode(':', $dimension, 2)[1]
            : $dimension;
        try {
            [$column, $row] = Coordinate::coordinateFromString($last);
            $columnIndex = Coordinate::columnIndexFromString($column);
        } catch (\Throwable) {
            return [1, 1];
        }

        return [min($columnIndex, $columnLimit), min((int) $row, $rowLimit)];
    }

    private function isTruncated(string $dimension): bool
    {
        [$columns, $rows] = $this->dimension($dimension, PHP_INT_MAX, PHP_INT_MAX);

        return $columns > self::MAX_EXCEL_COLUMNS || $rows > self::MAX_EXCEL_ROWS;
    }

    /**
     * @return array{starts:array<string,array{rowspan:int,colspan:int}>,covered:array<string,bool>}
     */
    private function mergeState(array $ranges, int $maxColumn, int $maxRow): array
    {
        $starts = [];
        $covered = [];

        foreach ($ranges as $range) {
            [$start, $end] = array_pad(explode(':', $range, 2), 2, null);
            $end ??= $start;
            try {
                [$startColumn, $startRow] = Coordinate::coordinateFromString($start);
                [$endColumn, $endRow] = Coordinate::coordinateFromString($end);
                $startIndex = Coordinate::columnIndexFromString($startColumn);
                $endIndex = Coordinate::columnIndexFromString($endColumn);
            } catch (\Throwable) {
                continue;
            }

            if ($startIndex > $maxColumn || $startRow > $maxRow) {
                continue;
            }
            $starts[$start] = [
                'rowspan' => min($endRow, $maxRow) - $startRow + 1,
                'colspan' => min($endIndex, $maxColumn) - $startIndex + 1,
            ];
            for ($row = $startRow; $row <= min($endRow, $maxRow); $row++) {
                for ($column = $startIndex; $column <= min($endIndex, $maxColumn); $column++) {
                    $address = Coordinate::stringFromColumnIndex($column).$row;
                    if ($address !== $start) {
                        $covered[$address] = true;
                    }
                }
            }
        }

        return compact('starts', 'covered');
    }

    private function dimensionValue(
        array $dimensions,
        string $identityKey,
        string|int $identity,
        string $valueKey
    ): mixed {
        return collect($dimensions)->firstWhere($identityKey, $identity)[$valueKey] ?? null;
    }
}
