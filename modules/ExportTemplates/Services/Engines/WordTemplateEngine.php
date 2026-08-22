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
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class WordTemplateEngine implements TemplateEngineInterface
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const A_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    public function __construct(
        private readonly TemplateValueResolver $resolver,
        private readonly TemplateValueFormatter $formatter
    ) {}

    public function supports(OutputFormat $format): bool
    {
        return $format === OutputFormat::WORD;
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
            throw new \RuntimeException('Không tìm thấy file Word template Active.');
        }

        $destination = $this->temporaryPath('docx');
        if (! copy($source, $destination)) {
            throw new \RuntimeException('Không thể tạo bản sao Word template để render.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($destination) !== true) {
            @unlink($destination);
            throw new \RuntimeException('Không thể mở bản sao Word template.');
        }

        try {
            $targets = collect($version->manifest['targets'] ?? [])->keyBy('ref');
            $bindingRows = $this->bindings($version, $bindings);
            $parts = [];
            $handled = [];

            foreach ($bindingRows as $binding) {
                $target = $targets->get($binding->target_ref);
                if (! $target || ! $this->isCollectionBinding($binding)) {
                    continue;
                }
                [$dom, $xpath] = $this->part($zip, $parts, $target['part'] ?? 'word/document.xml');
                $this->applyTableCollection(
                    $dom,
                    $xpath,
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
                $partName = $target['part'] ?? 'word/document.xml';
                [$dom, $xpath] = $this->part($zip, $parts, $partName);
                $this->applyScalar(
                    $zip,
                    $dom,
                    $xpath,
                    $partName,
                    $target,
                    $binding,
                    $data
                );
            }

            foreach ($parts as $partName => [$dom]) {
                $zip->addFromString($partName, $dom->saveXML());
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($destination);
            throw $exception;
        }

        $zip->close();

        return $destination;
    }

    private function renderBuilder(array $schema, array $data): string
    {
        $word = new PhpWord();
        $section = $word->addSection([
            'orientation' => (($schema['page']['orientation'] ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape'),
            'paperSize' => 'A4',
        ]);
        foreach (($schema['blocks'] ?? []) as $block) {
            $type = (string) ($block['type'] ?? 'text');
            $props = is_array($block['props'] ?? null) ? $block['props'] : [];

            if ($type === 'header_pair') {
                $header = $section->addTable([
                    'borderSize' => 0,
                    'cellMargin' => 0,
                    'width' => 100 * 50,
                    'unit' => 'pct',
                ]);
                $headerRow = $header->addRow(900);
                foreach (['left_text', 'right_text'] as $key) {
                    $sideKey = $key === 'left_text' ? 'left_style' : 'right_style';
                    $sideStyle = is_array($props[$sideKey] ?? null) ? $props[$sideKey] : [];
                    $headerStyle = array_replace($props, $sideStyle);
                    $cell = $headerRow->addCell(4800, [
                        'valign' => 'center',
                        'borderSize' => 0,
                    ]);
                    $run = $cell->addTextRun([
                        'alignment' => 'center',
                        'spaceAfter' => 0,
                        'lineHeight' => 1.15,
                    ]);
                    $lines = preg_split(
                        '/\R/u',
                        $this->bindBuilderValue((string) ($props[$key] ?? ''), $data)
                    ) ?: [''];
                    foreach ($lines as $index => $line) {
                        if ($index > 0) {
                            $run->addTextBreak();
                        }
                        $run->addText($line, [
                            'bold' => true,
                            'italic' => (bool) ($headerStyle['italic'] ?? false),
                            'underline' => ! empty($headerStyle['underline']) ? 'single' : 'none',
                            'size' => (float) ($headerStyle['font_size'] ?? ($index === 0 ? 11 : 10.5)),
                            'color' => ltrim((string) ($headerStyle['color'] ?? '000000'), '#'),
                        ]);
                    }
                }
                $section->addTextBreak();
                continue;
            }

            if ($type === 'table') {
                $table = $section->addTable([
                    'borderSize' => 6,
                    'borderColor' => ltrim((string) ($props['border_color'] ?? '475569'), '#'),
                    'cellMargin' => (int) ($props['padding'] ?? 80),
                ]);
                $rows = max(1, (int) ($props['rows'] ?? 2));
                $cols = max(1, (int) ($props['columns'] ?? 4));
                $items = ! empty($props['collection_key']) ? $this->resolver->collection($data, (string) $props['collection_key']) : [null];
                foreach ($items as $item) {
                    for ($r = 0; $r < $rows; $r++) {
                        $row = $table->addRow((int) ($props['row_height'] ?? 360));
                        for ($c = 0; $c < $cols; $c++) {
                            $columnKey = $props['column_bindings'][$c] ?? null;
                            $cellText = $props['cell_text'][$r][$c] ?? null;
                            $value = is_string($cellText) && $cellText !== ''
                                ? $cellText
                                : ($r === 0
                                    ? ($props['header_'.$c] ?? $columnKey ?? '')
                                    : ($props['cell_'.$r.'_'.$c] ?? ''));
                            if ($r > 0 && $columnKey && (! is_string($cellText) || $cellText === '')) {
                                $value = $this->formatter->format($this->resolver->resolve($data, (string) $columnKey, is_array($item) ? $item : null));
                            }
                            $cellStyle = is_array($props['cell_styles'][$r][$c] ?? null)
                                ? $props['cell_styles'][$r][$c]
                                : [];
                            $resolvedStyle = array_replace($props, $cellStyle);
                            $row->addCell((int) ($props['column_width'] ?? 1400), [
                                'valign' => 'center',
                            ])->addText(
                                $this->bindBuilderValue((string) $value, $data, is_array($item) ? $item : null),
                                [
                                    'bold' => $r === 0 || (bool) ($resolvedStyle['bold'] ?? false),
                                    'italic' => (bool) ($resolvedStyle['italic'] ?? false),
                                    'underline' => ! empty($resolvedStyle['underline']) ? 'single' : 'none',
                                    'size' => (float) ($resolvedStyle['font_size'] ?? 10),
                                    'color' => ltrim((string) ($resolvedStyle['color'] ?? '000000'), '#'),
                                    'bgColor' => ltrim((string) ($resolvedStyle['background'] ?? 'FFFFFF'), '#'),
                                ],
                                [
                                    'alignment' => (string) ($resolvedStyle['align'] ?? 'center'),
                                    'spaceAfter' => 0,
                                    'lineHeight' => (float) ($resolvedStyle['line_height'] ?? 1.0),
                                ]
                            );
                        }
                    }
                }
                continue;
            }

            if ($type === 'page_break') {
                $section->addPageBreak();
                continue;
            }
            if ($type === 'spacer') {
                $section->addTextBreak(max(1, (int) round(((float) ($props['height'] ?? 24)) / 18)));
                continue;
            }
            if ($type === 'divider') {
                $section->addText('', [], [
                    'borderBottomSize' => 6,
                    'borderBottomColor' => ltrim((string) ($props['border_color'] ?? '64748B'), '#'),
                    'spaceAfter' => 80,
                ]);
                continue;
            }

            $text = $this->bindBuilderValue((string) ($props['text'] ?? $props['label'] ?? ''), $data);
            $fontStyle = [
                'bold' => $type === 'heading' || (bool) ($props['bold'] ?? false),
                'italic' => (bool) ($props['italic'] ?? false),
                'underline' => ! empty($props['underline']) ? 'single' : 'none',
                'size' => (float) ($props['font_size'] ?? ($type === 'heading' ? 14 : 11)),
            ];
            if (! empty($props['color'])) {
                $fontStyle['color'] = ltrim((string) $props['color'], '#');
            }
            if (! empty($props['background'])) {
                $fontStyle['bgColor'] = ltrim((string) $props['background'], '#');
            }
            $section->addText($text, $fontStyle, [
                'alignment' => (string) ($props['align'] ?? 'left'),
                'lineHeight' => (float) ($props['line_height'] ?? 1.2),
                'spaceBefore' => max(0, (int) (($props['margin'] ?? 0) * 15)),
                'spaceAfter' => max(0, (int) (($props['margin'] ?? 0) * 15)),
            ]);
        }
        $destination = $this->temporaryPath('docx');
        IOFactory::createWriter($word, 'Word2007')->save($destination);
        return $destination;
    }

    private function bindBuilderValue(string $value, array $data, ?array $item = null): string
    {
        return (string) preg_replace_callback('/\{\{\s*([^}]+?)\s*\}\}/', function (array $match) use ($data, $item): string {
            return $this->formatter->format($this->resolver->resolve($data, trim($match[1]), $item));
        }, $value);
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
     * @param  array<string, array{\DOMDocument,\DOMXPath}>  $parts
     * @return array{\DOMDocument,\DOMXPath}
     */
    private function part(\ZipArchive $zip, array &$parts, string $partName): array
    {
        if (isset($parts[$partName])) {
            return $parts[$partName];
        }

        $xml = $zip->getFromName($partName);
        if (! is_string($xml) || $xml === '') {
            throw new \RuntimeException("Không thể đọc Word part [{$partName}].");
        }

        $dom = new \DOMDocument;
        if (! $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException("Word part [{$partName}] không hợp lệ.");
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        $xpath->registerNamespace('r', self::R_NS);
        $xpath->registerNamespace('a', self::A_NS);

        return $parts[$partName] = [$dom, $xpath];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $targets
     * @param  array<string, bool>  $handled
     */
    private function applyTableCollection(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        array $target,
        ExportTemplateBinding $binding,
        Collection $bindings,
        Collection $targets,
        array $data,
        array &$handled
    ): void {
        if (($target['kind'] ?? null) !== 'table') {
            return;
        }
        $items = $this->resolver->collection($data, $binding->data_key);
        $table = $xpath->query('//w:tbl[not(ancestor::w:tbl)]')
            ?->item((int) ($target['table_index'] ?? 0));
        if (! $table) {
            return;
        }

        $prefix = $binding->data_key.'[].';
        $childBindings = [];
        $prototypeRowIndex = null;
        foreach ($bindings as $child) {
            if (! str_starts_with($child->data_key, $prefix)) {
                continue;
            }
            $childTarget = $targets->get($child->target_ref);
            if (
                ! $childTarget
                || ($childTarget['kind'] ?? null) !== 'table_cell'
                || (int) ($childTarget['table_index'] ?? -1) !== (int) ($target['table_index'] ?? 0)
            ) {
                continue;
            }
            $rowIndex = (int) ($childTarget['row_index'] ?? 0);
            $prototypeRowIndex ??= $rowIndex;
            if ($rowIndex === $prototypeRowIndex) {
                $childBindings[(int) ($childTarget['cell_index'] ?? 0)] = $child;
            }
        }
        $prototypeRowIndex ??= 1;
        $rows = $xpath->query('./w:tr', $table);
        $prototype = $rows?->item($prototypeRowIndex);
        if (! $prototype) {
            return;
        }

        if ($items === []) {
            $this->setNodeText($xpath, $prototype, '');
            $handled[$binding->target_ref] = true;

            return;
        }

        $reference = $prototype;
        foreach ($items as $index => $item) {
            $row = $index === 0 ? $prototype : $prototype->cloneNode(true);
            if ($index > 0) {
                $reference->parentNode?->insertBefore($row, $reference->nextSibling);
                $reference = $row;
            }
            $cells = $xpath->query('./w:tc', $row);
            foreach ($cells ?: [] as $cell) {
                $this->applyPresentation($dom, $xpath, $cell, $binding, true);
            }
            if ($childBindings === []) {
                foreach (array_values($item) as $cellIndex => $value) {
                    $cell = $cells?->item($cellIndex);
                    if ($cell) {
                        $this->setNodeText($xpath, $cell, $this->formatter->format($value));
                    }
                }

                continue;
            }
            foreach ($childBindings as $cellIndex => $child) {
                $cell = $cells?->item($cellIndex);
                if (! $cell) {
                    continue;
                }
                $value = $this->resolver->resolve($data, $child->data_key, $item);
                $this->setNodeText(
                    $xpath,
                    $cell,
                    $this->formatter->format($value, $child->formatter)
                );
                $this->applyPresentation($dom, $xpath, $cell, $child, true);
                $handled[$child->target_ref] = true;
            }
        }

        $handled[$binding->target_ref] = true;
    }

    private function applyScalar(
        \ZipArchive $zip,
        \DOMDocument $dom,
        \DOMXPath $xpath,
        string $partName,
        array $target,
        ExportTemplateBinding $binding,
        array $data
    ): void {
        $kind = (string) ($target['kind'] ?? '');
        $value = $this->resolver->resolve($data, $binding->data_key);
        $formatted = $this->formatter->format($value, $binding->formatter);
        $node = null;

        if ($kind === 'placeholder') {
            $node = $xpath->query('//w:p')->item((int) ($target['paragraph_index'] ?? 0));
            if ($node) {
                $this->replacePlaceholder(
                    $xpath,
                    $node,
                    (string) ($target['data_key'] ?? ''),
                    $formatted
                );
            }
        } elseif ($kind === 'bookmark') {
            $node = $this->bookmark($xpath, (string) ($target['name'] ?? ''));
            if ($node) {
                $paragraph = $this->ancestor($node, 'p') ?: $node->parentNode;
                $this->setNodeText($xpath, $paragraph, $formatted);
                $node = $paragraph;
            }
        } elseif (in_array($kind, ['content_control', 'image_control'], true)) {
            $node = $xpath->query('//w:sdt')->item((int) $this->targetIndex($target));
            if ($kind === 'image_control' && is_file((string) $value) && $node) {
                $blip = $xpath->query('.//a:blip', $node)?->item(0);
                $relationshipId = $blip instanceof \DOMElement
                    ? $blip->getAttributeNS(self::R_NS, 'embed')
                    : '';
                $this->replaceImage($zip, $partName, $relationshipId, (string) $value);
            } elseif ($node) {
                $this->setNodeText($xpath, $node, $formatted);
            }
        } elseif ($kind === 'table_cell') {
            $table = $xpath->query('//w:tbl[not(ancestor::w:tbl)]')
                ?->item((int) ($target['table_index'] ?? 0));
            $row = $table ? $xpath->query('./w:tr', $table)?->item((int) ($target['row_index'] ?? 0)) : null;
            $node = $row ? $xpath->query('./w:tc', $row)?->item((int) ($target['cell_index'] ?? 0)) : null;
            if ($node) {
                $this->setNodeText($xpath, $node, $formatted);
            }
        } elseif ($kind === 'image' && is_file((string) $value)) {
            $this->replaceImage(
                $zip,
                $partName,
                (string) ($target['relationship_id'] ?? ''),
                (string) $value
            );
        }

        if ($node) {
            $this->applyPresentation($dom, $xpath, $node, $binding, $kind === 'table_cell');
        }
    }

    private function replacePlaceholder(
        \DOMXPath $xpath,
        \DOMNode $paragraph,
        string $originalDataKey,
        string $value
    ): void {
        $textNodes = $xpath->query('.//w:t', $paragraph);
        if (! $textNodes || $textNodes->length === 0) {
            return;
        }
        $fullText = '';
        foreach ($textNodes as $textNode) {
            $fullText .= $textNode->textContent;
        }
        $pattern = $originalDataKey !== ''
            ? '/\{\{\s*'.preg_quote($originalDataKey, '/').'\s*\}\}/u'
            : '/\{\{.*?\}\}/u';
        $replaced = preg_replace($pattern, $value, $fullText, 1);
        $this->replaceTextNodes($textNodes, $replaced ?? $value);
    }

    private function setNodeText(\DOMXPath $xpath, \DOMNode $node, string $value): void
    {
        $textNodes = $xpath->query('.//w:t', $node);
        if (! $textNodes || $textNodes->length === 0) {
            return;
        }
        $this->replaceTextNodes($textNodes, $value);
    }

    private function replaceTextNodes(\DOMNodeList $nodes, string $value): void
    {
        foreach ($nodes as $index => $node) {
            $node->nodeValue = $index === 0 ? $value : '';
        }
    }

    private function bookmark(\DOMXPath $xpath, string $name): ?\DOMNode
    {
        foreach ($xpath->query('//w:bookmarkStart') ?: [] as $bookmark) {
            if (
                $bookmark instanceof \DOMElement
                && $bookmark->getAttributeNS(self::W_NS, 'name') === $name
            ) {
                return $bookmark;
            }
        }

        return null;
    }

    private function targetIndex(array $target): int
    {
        $segments = explode(':', (string) ($target['ref'] ?? ''));

        return is_numeric(end($segments)) ? (int) end($segments) : 0;
    }

    private function ancestor(\DOMNode $node, string $localName): ?\DOMNode
    {
        $cursor = $node;
        while ($cursor->parentNode) {
            if ($cursor->localName === $localName && $cursor->namespaceURI === self::W_NS) {
                return $cursor;
            }
            $cursor = $cursor->parentNode;
        }

        return null;
    }

    private function applyPresentation(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        \DOMNode $node,
        ExportTemplateBinding $binding,
        bool $isCell
    ): void {
        $style = $binding->style_overrides ?? [];
        $options = $binding->options ?? [];
        $paragraph = $node->localName === 'p' ? $node : $xpath->query('.//w:p', $node)?->item(0);
        $run = $paragraph ? $xpath->query('.//w:r', $paragraph)?->item(0) : null;

        if ($paragraph && ! empty($style['align'])) {
            $pPr = $this->child($dom, $paragraph, 'pPr', true);
            $jc = $this->child($dom, $pPr, 'jc', true);
            $jc->setAttributeNS(self::W_NS, 'w:val', $style['align']);
        }
        if ($paragraph && isset($style['margin'])) {
            $pPr = $this->child($dom, $paragraph, 'pPr', true);
            $spacing = $this->child($dom, $pPr, 'spacing', true);
            $twips = (string) round((float) $style['margin'] * 15);
            $spacing->setAttributeNS(self::W_NS, 'w:before', $twips);
            $spacing->setAttributeNS(self::W_NS, 'w:after', $twips);
        }
        if ($run) {
            $rPr = $this->child($dom, $run, 'rPr', true);
            if (! empty($style['font_name'])) {
                $fonts = $this->child($dom, $rPr, 'rFonts', true);
                $fonts->setAttributeNS(self::W_NS, 'w:ascii', $style['font_name']);
                $fonts->setAttributeNS(self::W_NS, 'w:hAnsi', $style['font_name']);
            }
            if (! empty($style['font_size'])) {
                $size = $this->child($dom, $rPr, 'sz', true);
                $size->setAttributeNS(self::W_NS, 'w:val', (string) round($style['font_size'] * 2));
            }
            $this->booleanProperty($dom, $rPr, 'b', $style['bold'] ?? null);
            $this->booleanProperty($dom, $rPr, 'i', $style['italic'] ?? null);
        }

        if (! $isCell) {
            return;
        }
        $cell = $node->localName === 'tc' ? $node : $this->ancestor($node, 'tc');
        if (! $cell) {
            return;
        }
        $tcPr = $this->child($dom, $cell, 'tcPr', true);
        if (! empty($style['width']) || ! empty($options['column_width'])) {
            $width = $this->child($dom, $tcPr, 'tcW', true);
            $pixels = (float) ($options['column_width'] ?? $style['width']);
            $width->setAttributeNS(self::W_NS, 'w:w', (string) round($pixels * 15));
            $width->setAttributeNS(self::W_NS, 'w:type', 'dxa');
        }
        if (! empty($style['vertical_align'])) {
            $vAlign = $this->child($dom, $tcPr, 'vAlign', true);
            $vAlign->setAttributeNS(
                self::W_NS,
                'w:val',
                $style['vertical_align'] === 'middle' ? 'center' : $style['vertical_align']
            );
        }
        if (! empty($style['border_style'])) {
            $borders = $this->child($dom, $tcPr, 'tcBorders', true);
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $border = $this->child($dom, $borders, $side, true);
                $border->setAttributeNS(self::W_NS, 'w:val', match ($style['border_style']) {
                    'none' => 'nil',
                    'dashed' => 'dashed',
                    'dotted' => 'dotted',
                    'double' => 'double',
                    default => 'single',
                });
                $border->setAttributeNS(self::W_NS, 'w:sz', match ($style['border_style']) {
                    'medium' => '8',
                    'thick' => '12',
                    default => '4',
                });
                $border->setAttributeNS(
                    self::W_NS,
                    'w:color',
                    ltrim((string) ($style['border_color'] ?? '#000000'), '#')
                );
            }
        }
        if (isset($style['padding'])) {
            $cellMargins = $this->child($dom, $tcPr, 'tcMar', true);
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $margin = $this->child($dom, $cellMargins, $side, true);
                $margin->setAttributeNS(
                    self::W_NS,
                    'w:w',
                    (string) round((float) $style['padding'] * 15)
                );
                $margin->setAttributeNS(self::W_NS, 'w:type', 'dxa');
            }
        }

        $action = $options['cell_action'] ?? 'none';
        if ($action === 'merge') {
            $span = $this->child($dom, $tcPr, 'gridSpan', true);
            $span->setAttributeNS(self::W_NS, 'w:val', '2');
        } elseif ($action === 'split') {
            foreach (['gridSpan', 'vMerge'] as $name) {
                $property = $this->child($dom, $tcPr, $name, false);
                if ($property) {
                    $tcPr->removeChild($property);
                }
            }
        }

        $row = $this->ancestor($cell, 'tr');
        if ($row && (! empty($style['height']) || ! empty($options['row_height']))) {
            $trPr = $this->child($dom, $row, 'trPr', true);
            $height = $this->child($dom, $trPr, 'trHeight', true);
            $pixels = (float) ($options['row_height'] ?? $style['height']);
            $height->setAttributeNS(self::W_NS, 'w:val', (string) round($pixels * 15));
        }
    }

    private function child(
        \DOMDocument $dom,
        \DOMNode $parent,
        string $localName,
        bool $create
    ): ?\DOMElement {
        foreach ($parent->childNodes as $child) {
            if ($child->localName === $localName && $child->namespaceURI === self::W_NS) {
                return $child instanceof \DOMElement ? $child : null;
            }
        }
        if (! $create) {
            return null;
        }

        $element = $dom->createElementNS(self::W_NS, 'w:'.$localName);
        $parent->insertBefore($element, $parent->firstChild);

        return $element;
    }

    private function booleanProperty(
        \DOMDocument $dom,
        \DOMElement $parent,
        string $name,
        mixed $value
    ): void {
        if ($value === null) {
            return;
        }
        $property = $this->child($dom, $parent, $name, (bool) $value);
        if ($property && ! $value) {
            $parent->removeChild($property);
        }
    }

    private function replaceImage(
        \ZipArchive $zip,
        string $partName,
        string $relationshipId,
        string $imagePath
    ): void {
        if ($relationshipId === '' || ! is_file($imagePath)) {
            return;
        }
        $relsName = dirname($partName).'/_rels/'.basename($partName).'.rels';
        $xml = $zip->getFromName($relsName);
        if (! is_string($xml)) {
            return;
        }
        $dom = new \DOMDocument;
        if (! $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            return;
        }
        foreach ($dom->getElementsByTagName('Relationship') as $relationship) {
            if (
                $relationship instanceof \DOMElement
                && $relationship->getAttribute('Id') === $relationshipId
            ) {
                $target = str_replace('\\', '/', $relationship->getAttribute('Target'));
                $mediaName = $this->normalizeZipPath(dirname($partName).'/'.$target);
                if (! str_starts_with($mediaName, 'word/')) {
                    return;
                }
                $contents = file_get_contents($imagePath);
                if ($contents !== false) {
                    $zip->addFromString($mediaName, $contents);
                }
                break;
            }
        }
    }

    private function temporaryPath(string $extension): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'lms-template-'.Str::uuid().'.'.$extension;
    }

    private function normalizeZipPath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}
