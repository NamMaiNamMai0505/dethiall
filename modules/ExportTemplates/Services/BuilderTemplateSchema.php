<?php

namespace Modules\ExportTemplates\Services;

use App\Support\SystemSettings;
use InvalidArgumentException;

/** Chuẩn JSON trung gian dùng chung cho Dashboard, LMS và Quản lý điểm. */
final class BuilderTemplateSchema
{
    public const VERSION = '1.0';

    public static function empty(string $format = 'excel'): array
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
        $nationalHeading = (string) SystemSettings::get(
            'shared',
            'national_heading',
            'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM'
        );
        $nationalMotto = (string) SystemSettings::get(
            'shared',
            'national_motto',
            'Độc lập - Tự do - Hạnh phúc'
        );
        $pageSize = (string) SystemSettings::get('shared', 'default_page_size', 'A4');
        $orientation = (string) SystemSettings::get('shared', 'default_orientation', 'landscape');

        return [
            'schema_version' => self::VERSION,
            'format' => in_array($format, ['word', 'excel'], true) ? $format : 'excel',
            'page' => [
                'size' => in_array($pageSize, ['A4', 'A3', 'Letter'], true) ? $pageSize : 'A4',
                'orientation' => in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'landscape',
                'margin' => ['top' => 12, 'right' => 12, 'bottom' => 12, 'left' => 12],
            ],
            'blocks' => [[
                'id' => 'header-pair',
                'type' => 'header_pair',
                'props' => [
                    'left_text' => $parent."\n".$organization,
                    'right_text' => $nationalHeading."\n".$nationalMotto,
                    'align' => 'center',
                    'bold' => true,
                    'font_size' => 12,
                ],
            ]],
        ];
    }

    public static function normalize(array $schema, string $format = 'excel'): array
    {
        $defaults = self::empty($format);
        $submittedBlocks = is_array($schema['blocks'] ?? null) ? $schema['blocks'] : $defaults['blocks'];
        unset($schema['blocks']);

        $result = array_replace_recursive($defaults, $schema);
        $result['schema_version'] = self::VERSION;
        $result['blocks'] = array_values(array_filter(
            $submittedBlocks,
            static fn (mixed $block): bool => is_array($block) && isset($block['type'])
        ));

        $headerIndex = null;
        foreach ($result['blocks'] as $index => $candidate) {
            if (($candidate['type'] ?? null) === 'header_pair') {
                $headerIndex = $index;
                break;
            }
        }
        if ($headerIndex === null) {
            array_unshift($result['blocks'], $defaults['blocks'][0]);
            $headerIndex = 0;
        } elseif ($headerIndex > 0) {
            $headerBlock = $result['blocks'][$headerIndex];
            array_splice($result['blocks'], $headerIndex, 1);
            array_unshift($result['blocks'], $headerBlock);
            $headerIndex = 0;
        }

        $headerDefaults = $defaults['blocks'][0]['props'];
        $headerProps = is_array($result['blocks'][$headerIndex]['props'] ?? null)
            ? $result['blocks'][$headerIndex]['props']
            : [];
        foreach (['left_text', 'right_text'] as $key) {
            if (trim((string) ($headerProps[$key] ?? '')) === '') {
                $headerProps[$key] = $headerDefaults[$key];
            }
        }
        $result['blocks'][$headerIndex]['props'] = array_replace($headerDefaults, $headerProps);

        foreach ($result['blocks'] as &$block) {
            $block['id'] = (string) ($block['id'] ?? ('block_'.bin2hex(random_bytes(5))));
            $block['type'] = (string) $block['type'];
            $block['props'] = is_array($block['props'] ?? null) ? $block['props'] : [];
            $block['children'] = is_array($block['children'] ?? null) ? $block['children'] : [];
        }
        unset($block);

        return $result;
    }

    public static function validate(array $schema): array
    {
        $errors = [];
        if (! isset($schema['blocks']) || ! is_array($schema['blocks'])) {
            $errors['blocks'][] = 'Template phải có danh sách blocks.';
        }

        foreach (($schema['blocks'] ?? []) as $index => $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                $errors["blocks.{$index}.type"][] = 'Block phải có type hợp lệ.';
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $schema;
    }
}
