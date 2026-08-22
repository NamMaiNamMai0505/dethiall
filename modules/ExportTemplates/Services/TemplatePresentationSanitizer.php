<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Exceptions\InvalidTemplateBindingException;

class TemplatePresentationSanitizer
{
    private const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    private const VERTICAL_ALIGNMENTS = ['top', 'middle', 'bottom'];

    private const BORDER_STYLES = ['none', 'thin', 'medium', 'thick', 'dashed', 'dotted', 'double'];

    private const CELL_ACTIONS = ['none', 'merge', 'split'];

    /**
     * @return array{style:array<string,mixed>,options:array<string,mixed>}
     */
    public function sanitize(array $style, array $options, string $targetKind): array
    {
        $errors = [];
        $cleanStyle = [];
        $cleanOptions = [];

        $this->string($style, $cleanStyle, 'font_name', 100);
        $this->number($style, $cleanStyle, 'font_size', 6, 72, $errors);
        $this->boolean($style, $cleanStyle, 'bold');
        $this->boolean($style, $cleanStyle, 'italic');
        $this->choice($style, $cleanStyle, 'align', self::ALIGNMENTS, $errors);
        $this->choice(
            $style,
            $cleanStyle,
            'vertical_align',
            self::VERTICAL_ALIGNMENTS,
            $errors
        );
        $this->number($style, $cleanStyle, 'width', 1, 2000, $errors);
        $this->number($style, $cleanStyle, 'height', 1, 2000, $errors);
        $this->choice($style, $cleanStyle, 'border_style', self::BORDER_STYLES, $errors);
        $this->color($style, $cleanStyle, 'border_color', $errors);
        $this->number($style, $cleanStyle, 'padding', 0, 200, $errors);
        $this->number($style, $cleanStyle, 'margin', 0, 200, $errors);

        $this->number($options, $cleanOptions, 'row_height', 1, 1000, $errors);
        $this->number($options, $cleanOptions, 'column_width', 1, 1000, $errors);
        $this->choice($options, $cleanOptions, 'cell_action', self::CELL_ACTIONS, $errors);
        $this->string($options, $cleanOptions, 'merge_range', 32);

        $cellAction = $cleanOptions['cell_action'] ?? 'none';
        if (
            $cellAction !== 'none'
            && ! in_array($targetKind, ['cell', 'merged_range', 'table_cell'], true)
        ) {
            $errors[] = 'Chỉ target dạng cell mới được phép Merge/Split.';
        }

        if (
            ! empty($cleanOptions['merge_range'])
            && ! preg_match('/^[A-Z]{1,3}\d+:[A-Z]{1,3}\d+$/', $cleanOptions['merge_range'])
        ) {
            $errors[] = 'Merge range phải có dạng A1:B2.';
        }

        if ($cellAction !== 'merge') {
            unset($cleanOptions['merge_range']);
        } elseif (empty($cleanOptions['merge_range']) && $targetKind === 'cell') {
            $errors[] = 'Cần nhập vùng Merge khi chọn thao tác Merge.';
        }

        if ($errors !== []) {
            throw new InvalidTemplateBindingException('Thuộc tính trình bày không hợp lệ.', $errors);
        }

        return [
            'style' => $cleanStyle,
            'options' => $cleanOptions,
        ];
    }

    private function string(array $source, array &$target, string $key, int $max): void
    {
        $value = trim((string) ($source[$key] ?? ''));
        if ($value !== '') {
            $target[$key] = mb_substr($value, 0, $max);
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function number(
        array $source,
        array &$target,
        string $key,
        float $min,
        float $max,
        array &$errors
    ): void {
        if (! isset($source[$key]) || $source[$key] === '') {
            return;
        }

        if (! is_numeric($source[$key])) {
            $errors[] = "{$key} phải là số.";

            return;
        }

        $value = (float) $source[$key];
        if ($value < $min || $value > $max) {
            $errors[] = "{$key} phải nằm trong khoảng {$min}-{$max}.";

            return;
        }

        $target[$key] = $value;
    }

    private function boolean(array $source, array &$target, string $key): void
    {
        if (array_key_exists($key, $source)) {
            $target[$key] = filter_var($source[$key], FILTER_VALIDATE_BOOL);
        }
    }

    /**
     * @param  list<string>  $allowed
     * @param  list<string>  $errors
     */
    private function choice(
        array $source,
        array &$target,
        string $key,
        array $allowed,
        array &$errors
    ): void {
        $value = trim((string) ($source[$key] ?? ''));
        if ($value === '') {
            return;
        }

        if (! in_array($value, $allowed, true)) {
            $errors[] = "{$key} không thuộc danh sách cho phép.";

            return;
        }

        $target[$key] = $value;
    }

    /**
     * @param  list<string>  $errors
     */
    private function color(
        array $source,
        array &$target,
        string $key,
        array &$errors
    ): void {
        $value = strtoupper(trim((string) ($source[$key] ?? '')));
        if ($value === '') {
            return;
        }
        if (! preg_match('/^#[0-9A-F]{6}$/', $value)) {
            $errors[] = "{$key} phải có dạng #RRGGBB.";

            return;
        }

        $target[$key] = $value;
    }
}
