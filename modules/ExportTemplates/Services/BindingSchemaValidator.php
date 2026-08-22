<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Enums\TemplateBindingType;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Exceptions\InvalidTemplateBindingException;
use Modules\ExportTemplates\Models\ExportTemplateVersion;

class BindingSchemaValidator
{
    private const COLLECTION_TARGETS = [
        'table',
        'named_range',
        'merged_range',
    ];

    private const IMAGE_TARGETS = [
        'image',
        'image_control',
        'cell',
        'placeholder',
        'content_control',
    ];

    public function __construct(
        private readonly TemplateDataRegistry $registry,
        private readonly TemplateDataExplorer $explorer,
        private readonly TemplateDataSecurityPolicy $security
    ) {}

    /**
     * @return array{
     *   target:array<string,mixed>,
     *   field:array<string,mixed>,
     *   binding_type:TemplateBindingType
     * }
     */
    public function validate(
        ExportTemplateVersion $version,
        string $targetRef,
        string $dataKey
    ): array {
        $version->loadMissing('template');
        $errors = [];

        if ($version->status !== TemplateStatus::DRAFT || $version->activations()->exists()) {
            $errors[] = 'Chỉ được sửa binding trên phiên bản nháp chưa Active.';
        }

        $target = collect($version->manifest['targets'] ?? [])
            ->first(fn (array $item): bool => ($item['ref'] ?? null) === $targetRef);
        if (! $target) {
            $errors[] = "Target [{$targetRef}] không tồn tại trong manifest của phiên bản.";
        }

        if (! $this->security->isAllowed($dataKey)) {
            $errors[] = "Trường dữ liệu [{$dataKey}] thuộc nhóm bị khóa bảo mật.";
        }

        if (! $this->registry->has((string) $version->template?->feature_key)) {
            $errors[] = 'Feature của template chưa có Data Provider.';
            $field = null;
        } else {
            $schema = $this->registry->get($version->template->feature_key)->schema();
            $field = $this->explorer->find($schema, $dataKey);
            if (! $field || ($field['bindable'] ?? true) === false) {
                $errors[] = "Trường [{$dataKey}] không tồn tại trong schema allowlist.";
            }
        }

        $targetKind = (string) ($target['kind'] ?? '');
        $fieldType = (string) ($field['type'] ?? '');

        if ($fieldType === 'collection' && ! in_array($targetKind, self::COLLECTION_TARGETS, true)) {
            $errors[] = 'Binding danh sách chỉ áp dụng cho target dạng bảng/vùng lặp.';
        }

        if ($fieldType === 'image' && ! in_array($targetKind, self::IMAGE_TARGETS, true)) {
            $errors[] = 'Dữ liệu ảnh không tương thích với loại target đã chọn.';
        }

        if ($errors !== []) {
            throw new InvalidTemplateBindingException(
                'Binding không hợp lệ.',
                $errors
            );
        }

        return [
            'target' => $target,
            'field' => $field,
            'binding_type' => $this->bindingType($fieldType, $targetKind),
        ];
    }

    private function bindingType(string $fieldType, string $targetKind): TemplateBindingType
    {
        if ($fieldType === 'collection') {
            return $targetKind === 'table'
                ? TemplateBindingType::TABLE
                : TemplateBindingType::REPEATING_ROW;
        }

        return match ($fieldType) {
            'date' => TemplateBindingType::DATE,
            'number', 'integer' => TemplateBindingType::NUMBER,
            'image' => TemplateBindingType::IMAGE,
            default => TemplateBindingType::SCALAR,
        };
    }
}
