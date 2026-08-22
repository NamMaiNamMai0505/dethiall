<?php

namespace Modules\ExportTemplates\Services\Parsers;

use Modules\ExportTemplates\Contracts\TemplateParserInterface;
use Modules\ExportTemplates\Exceptions\InvalidTemplateException;

class WordTemplateParser implements TemplateParserInterface
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const A_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    public function supports(string $fileExtension): bool
    {
        return strtolower($fileExtension) === 'docx';
    }

    public function parse(string $absolutePath): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            throw new InvalidTemplateException(
                'File Word bị hỏng.',
                ['Không thể mở cấu trúc DOCX.']
            );
        }

        try {
            if ($zip->locateName('word/document.xml') === false) {
                throw new InvalidTemplateException(
                    'File Word thiếu tài liệu chính.',
                    ['Không tìm thấy word/document.xml.']
                );
            }

            $partNames = ['word/document.xml'];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);
                if (preg_match('#^word/(header|footer)\d+\.xml$#', $name)) {
                    $partNames[] = $name;
                }
            }

            $targets = [];
            $elements = [];
            $placeholders = [];
            $parts = [];
            $errors = [];
            $warnings = [];
            $bookmarkNames = [];
            $contentTags = [];
            $layout = [];

            foreach (array_values(array_unique($partNames)) as $partName) {
                $xml = $zip->getFromName($partName);
                if (! is_string($xml) || $xml === '') {
                    $errors[] = "Không thể đọc part [{$partName}].";

                    continue;
                }

                [$dom, $xpath] = $this->loadXml($xml, $partName);
                $partToken = rawurlencode($partName);
                $partType = $partName === 'word/document.xml'
                    ? 'document'
                    : (str_contains($partName, '/header') ? 'header' : 'footer');
                $partSummary = [
                    'name' => $partName,
                    'type' => $partType,
                    'paragraph_count' => 0,
                    'table_count' => 0,
                    'image_count' => 0,
                    'bookmark_count' => 0,
                    'content_control_count' => 0,
                ];

                $paragraphs = $xpath->query('//w:p');
                if ($paragraphs !== false) {
                    foreach ($paragraphs as $paragraphIndex => $paragraph) {
                        $partSummary['paragraph_count']++;
                        $text = $this->nodeText($xpath, $paragraph);
                        if ($text === '') {
                            continue;
                        }

                        $elements[] = [
                            'ref' => "word:paragraph:{$partToken}:{$paragraphIndex}",
                            'kind' => 'paragraph',
                            'part' => $partName,
                            'part_type' => $partType,
                            'index' => $paragraphIndex,
                            'text' => $text,
                            'style' => $this->paragraphStyle($xpath, $paragraph),
                        ];

                        if (preg_match_all(
                            '/\{\{\s*([a-zA-Z0-9_.\[\]-]+)\s*\}\}/u',
                            $text,
                            $matches
                        )) {
                            foreach ($matches[1] as $occurrence => $dataKey) {
                                $targets[] = [
                                    'ref' => "word:placeholder:{$partToken}:p{$paragraphIndex}:{$occurrence}",
                                    'kind' => 'placeholder',
                                    'part' => $partName,
                                    'part_type' => $partType,
                                    'paragraph_index' => $paragraphIndex,
                                    'data_key' => $dataKey,
                                    'binding_type' => 'scalar',
                                    'split_run_safe' => true,
                                ];
                                $placeholders[] = $dataKey;
                            }
                        }
                    }
                }

                $bookmarks = $xpath->query('//w:bookmarkStart');
                if ($bookmarks !== false) {
                    foreach ($bookmarks as $bookmarkIndex => $bookmark) {
                        if (! $bookmark instanceof \DOMElement) {
                            continue;
                        }
                        $name = $bookmark->getAttributeNS(self::W_NS, 'name');
                        if ($name === '' || str_starts_with($name, '_')) {
                            continue;
                        }

                        $partSummary['bookmark_count']++;
                        if (isset($bookmarkNames[$name])) {
                            $errors[] = "Bookmark [{$name}] bị trùng.";
                        }
                        $bookmarkNames[$name] = true;
                        $targets[] = [
                            'ref' => "word:bookmark:{$partToken}:".rawurlencode($name),
                            'kind' => 'bookmark',
                            'name' => $name,
                            'bookmark_id' => $bookmark->getAttributeNS(self::W_NS, 'id'),
                            'part' => $partName,
                            'part_type' => $partType,
                            'text' => $this->ancestorParagraphText($xpath, $bookmark),
                            'binding_type' => 'scalar',
                        ];
                    }
                }

                $contentControls = $xpath->query('//w:sdt');
                if ($contentControls !== false) {
                    foreach ($contentControls as $controlIndex => $control) {
                        $partSummary['content_control_count']++;
                        $tag = $this->attributeFromQuery($xpath, './w:sdtPr/w:tag', 'val', $control);
                        $alias = $this->attributeFromQuery($xpath, './w:sdtPr/w:alias', 'val', $control);
                        $id = $this->attributeFromQuery($xpath, './w:sdtPr/w:id', 'val', $control);
                        $identity = $tag ?: ($alias ?: ($id ?: (string) $controlIndex));
                        $hasImage = $xpath->query('.//a:blip|.//w:drawing|.//w:pict', $control)?->length > 0;
                        $hasTable = $xpath->query('.//w:tbl', $control)?->length > 0;

                        if ($tag !== '') {
                            if (isset($contentTags[$tag])) {
                                $warnings[] = "Content control tag [{$tag}] xuất hiện nhiều lần.";
                            }
                            $contentTags[$tag] = true;
                        }

                        $targets[] = [
                            'ref' => "word:sdt:{$partToken}:".rawurlencode($identity).":{$controlIndex}",
                            'kind' => $hasImage ? 'image_control' : 'content_control',
                            'tag' => $tag ?: null,
                            'alias' => $alias ?: null,
                            'control_id' => $id ?: null,
                            'part' => $partName,
                            'part_type' => $partType,
                            'text' => $this->nodeText($xpath, $control),
                            'binding_type' => $hasImage ? 'image' : ($hasTable ? 'table' : 'scalar'),
                        ];
                    }
                }

                $tables = $xpath->query('//w:tbl[not(ancestor::w:tbl)]');
                if ($tables !== false) {
                    foreach ($tables as $tableIndex => $table) {
                        $partSummary['table_count']++;
                        $rows = [];
                        $rowNodes = $xpath->query('./w:tr', $table);
                        if ($rowNodes !== false) {
                            foreach ($rowNodes as $rowIndex => $row) {
                                $cells = [];
                                $cellNodes = $xpath->query('./w:tc', $row);
                                if ($cellNodes !== false) {
                                    foreach ($cellNodes as $cellIndex => $cell) {
                                        $cellElement = [
                                            'ref' => "word:cell:{$partToken}:t{$tableIndex}:r{$rowIndex}:c{$cellIndex}",
                                            'kind' => 'table_cell',
                                            'part' => $partName,
                                            'table_index' => $tableIndex,
                                            'row_index' => $rowIndex,
                                            'cell_index' => $cellIndex,
                                            'text' => $this->nodeText($xpath, $cell),
                                            'style' => $this->tableCellStyle($xpath, $cell),
                                        ];
                                        $cells[] = $cellElement;
                                        $elements[] = $cellElement;
                                        $targets[] = [
                                            'ref' => $cellElement['ref'],
                                            'kind' => 'table_cell',
                                            'part' => $partName,
                                            'part_type' => $partType,
                                            'table_index' => $tableIndex,
                                            'row_index' => $rowIndex,
                                            'cell_index' => $cellIndex,
                                            'binding_type' => 'scalar',
                                        ];
                                    }
                                }
                                $rows[] = [
                                    'index' => $rowIndex,
                                    'height' => $this->attributeFromQuery(
                                        $xpath,
                                        './w:trPr/w:trHeight',
                                        'val',
                                        $row
                                    ) ?: null,
                                    'cells' => $cells,
                                ];
                            }
                        }

                        $tableElement = [
                            'ref' => "word:table:{$partToken}:{$tableIndex}",
                            'kind' => 'table',
                            'part' => $partName,
                            'part_type' => $partType,
                            'index' => $tableIndex,
                            'rows' => $rows,
                        ];
                        $elements[] = $tableElement;
                        $targets[] = [
                            'ref' => $tableElement['ref'],
                            'kind' => 'table',
                            'part' => $partName,
                            'part_type' => $partType,
                            'table_index' => $tableIndex,
                            'binding_type' => 'table',
                        ];
                    }
                }

                $images = $xpath->query('//a:blip[@r:embed]');
                if ($images !== false) {
                    foreach ($images as $imageIndex => $image) {
                        if (! $image instanceof \DOMElement) {
                            continue;
                        }
                        $partSummary['image_count']++;
                        $relationshipId = $image->getAttributeNS(self::R_NS, 'embed');
                        $imageElement = [
                            'ref' => "word:image:{$partToken}:{$imageIndex}",
                            'kind' => 'image',
                            'part' => $partName,
                            'part_type' => $partType,
                            'relationship_id' => $relationshipId,
                        ];
                        $elements[] = $imageElement;
                        $targets[] = array_merge($imageElement, ['binding_type' => 'image']);
                    }
                }

                if ($partType === 'document') {
                    $layout = $this->pageLayout($xpath);
                }

                $parts[] = $partSummary;
            }

            return [
                'parser' => 'word-v1',
                'document' => [
                    'format' => 'word',
                    'parts' => $parts,
                    'layout' => $layout,
                    'has_header' => collect($parts)->contains('type', 'header'),
                    'has_footer' => collect($parts)->contains('type', 'footer'),
                ],
                'targets' => $targets,
                'elements' => $elements,
                'placeholders' => array_values(array_unique($placeholders)),
                'cell_map' => [],
                'hints' => [],
                'validation' => [
                    'valid' => $errors === [],
                    'errors' => array_values(array_unique($errors)),
                    'warnings' => array_values(array_unique($warnings)),
                ],
                'summary' => [
                    'part_count' => count($parts),
                    'bookmark_count' => count($bookmarkNames),
                    'content_control_count' => array_sum(array_column($parts, 'content_control_count')),
                    'table_count' => array_sum(array_column($parts, 'table_count')),
                    'image_count' => array_sum(array_column($parts, 'image_count')),
                ],
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{\DOMDocument,\DOMXPath}
     */
    private function loadXml(string $xml, string $partName): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            $message = $xmlErrors !== []
                ? trim($xmlErrors[0]->message)
                : 'XML không hợp lệ';
            throw new InvalidTemplateException(
                "Part [{$partName}] bị hỏng.",
                [$message]
            );
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        $xpath->registerNamespace('r', self::R_NS);
        $xpath->registerNamespace('a', self::A_NS);

        return [$dom, $xpath];
    }

    private function nodeText(\DOMXPath $xpath, \DOMNode $node): string
    {
        $parts = [];
        $textNodes = $xpath->query('.//w:t', $node);
        if ($textNodes !== false) {
            foreach ($textNodes as $textNode) {
                $parts[] = $textNode->textContent;
            }
        }

        return trim(implode('', $parts));
    }

    private function ancestorParagraphText(\DOMXPath $xpath, \DOMNode $node): string
    {
        $current = $node;
        while ($current && $current->parentNode) {
            if ($current->localName === 'p' && $current->namespaceURI === self::W_NS) {
                return $this->nodeText($xpath, $current);
            }
            $current = $current->parentNode;
        }

        return '';
    }

    private function attributeFromQuery(
        \DOMXPath $xpath,
        string $query,
        string $attribute,
        \DOMNode $context
    ): string {
        $node = $xpath->query($query, $context)?->item(0);

        return $node instanceof \DOMElement
            ? $node->getAttributeNS(self::W_NS, $attribute)
            : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraphStyle(\DOMXPath $xpath, \DOMNode $paragraph): array
    {
        $alignment = $this->attributeFromQuery($xpath, './w:pPr/w:jc', 'val', $paragraph);
        $run = $xpath->query('./w:r[1]', $paragraph)?->item(0);

        return [
            'alignment' => $alignment ?: null,
            'font' => $run ? [
                'name' => $this->attributeFromQuery($xpath, './w:rPr/w:rFonts', 'ascii', $run) ?: null,
                'size_half_points' => $this->attributeFromQuery($xpath, './w:rPr/w:sz', 'val', $run) ?: null,
                'bold' => $xpath->query('./w:rPr/w:b', $run)?->length > 0,
                'italic' => $xpath->query('./w:rPr/w:i', $run)?->length > 0,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tableCellStyle(\DOMXPath $xpath, \DOMNode $cell): array
    {
        $borders = [];
        foreach (['top', 'right', 'bottom', 'left', 'insideH', 'insideV'] as $side) {
            $border = $xpath->query("./w:tcPr/w:tcBorders/w:{$side}", $cell)?->item(0);
            if ($border instanceof \DOMElement) {
                $borders[$side] = [
                    'style' => $border->getAttributeNS(self::W_NS, 'val') ?: null,
                    'size' => $border->getAttributeNS(self::W_NS, 'sz') ?: null,
                    'color' => $border->getAttributeNS(self::W_NS, 'color') ?: null,
                ];
            }
        }

        $verticalMergeNode = $xpath->query('./w:tcPr/w:vMerge', $cell)?->item(0);
        $verticalMerge = null;
        if ($verticalMergeNode instanceof \DOMElement) {
            $verticalMerge = $verticalMergeNode->getAttributeNS(self::W_NS, 'val') ?: 'continue';
        }

        return [
            'width_twips' => $this->attributeFromQuery($xpath, './w:tcPr/w:tcW', 'w', $cell) ?: null,
            'grid_span' => (int) ($this->attributeFromQuery(
                $xpath,
                './w:tcPr/w:gridSpan',
                'val',
                $cell
            ) ?: 1),
            'vertical_merge' => $verticalMerge,
            'vertical_alignment' => $this->attributeFromQuery(
                $xpath,
                './w:tcPr/w:vAlign',
                'val',
                $cell
            ) ?: null,
            'shading' => $this->attributeFromQuery($xpath, './w:tcPr/w:shd', 'fill', $cell) ?: null,
            'borders' => $borders,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pageLayout(\DOMXPath $xpath): array
    {
        $section = $xpath->query('//w:sectPr[last()]')?->item(0);
        if (! $section) {
            return [];
        }

        return [
            'page_width_twips' => $this->attributeFromQuery($xpath, './w:pgSz', 'w', $section) ?: null,
            'page_height_twips' => $this->attributeFromQuery($xpath, './w:pgSz', 'h', $section) ?: null,
            'orientation' => $this->attributeFromQuery($xpath, './w:pgSz', 'orient', $section) ?: null,
            'margins_twips' => [
                'top' => $this->attributeFromQuery($xpath, './w:pgMar', 'top', $section) ?: null,
                'right' => $this->attributeFromQuery($xpath, './w:pgMar', 'right', $section) ?: null,
                'bottom' => $this->attributeFromQuery($xpath, './w:pgMar', 'bottom', $section) ?: null,
                'left' => $this->attributeFromQuery($xpath, './w:pgMar', 'left', $section) ?: null,
                'header' => $this->attributeFromQuery($xpath, './w:pgMar', 'header', $section) ?: null,
                'footer' => $this->attributeFromQuery($xpath, './w:pgMar', 'footer', $section) ?: null,
            ],
        ];
    }
}
